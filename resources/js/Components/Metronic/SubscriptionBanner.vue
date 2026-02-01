<script setup>
    import { computed, ref, onMounted } from 'vue';
    import { usePage, Link } from '@inertiajs/vue3';

    const page = usePage();

    const company = computed(() => page.props.company || null);
    const authUser = computed(() => page.props.auth?.user || null);
    const isPlatformAdmin = computed(() => authUser.value?.is_platform_admin || false);

    const subscriptionStatus = computed(() => company.value?.subscription_status || 'trial');
    const isOnTrial = computed(() => company.value?.is_on_trial || false);
    const trialDaysRemaining = computed(() => company.value?.trial_days_remaining || 0);

    // Dismiss state (session-based for non-critical banners)
    const dismissed = ref(false);

    onMounted(() => {
        const key = dismissKey.value;
        if (key && sessionStorage.getItem(key) === 'true') {
            dismissed.value = true;
        }
    });

    const dismissKey = computed(() => {
        if (!bannerConfig.value) return null;
        return `sub_banner_dismissed_${subscriptionStatus.value}_${trialDaysRemaining.value}`;
    });

    const dismiss = () => {
        dismissed.value = true;
        if (dismissKey.value) {
            sessionStorage.setItem(dismissKey.value, 'true');
        }
    };

    // Banner configuration based on status
    const bannerConfig = computed(() => {
        if (!company.value) return null;
        if (isPlatformAdmin.value) return null;

        const status = subscriptionStatus.value;

        // Expired
        if (status === 'expired') {
            return {
                type: 'critical',
                bgClass: 'bg-danger',
                icon: 'ki-filled ki-shield-cross',
                message: 'Your subscription has expired. Please upgrade to continue using all features.',
                ctaText: 'Upgrade Now',
                ctaLink: '/subscription/plans',
                dismissible: false,
            };
        }

        // Suspended
        if (status === 'suspended') {
            return {
                type: 'critical',
                bgClass: 'bg-danger-active',
                icon: 'ki-filled ki-lock-3',
                message: 'Your account has been suspended. Please renew your subscription.',
                ctaText: 'Renew Now',
                ctaLink: '/subscription/plans',
                dismissible: false,
            };
        }

        // Past due
        if (status === 'past_due') {
            return {
                type: 'warning',
                bgClass: 'bg-warning',
                icon: 'ki-filled ki-notification-bing',
                message: 'Your payment is past due. Update your payment to avoid service interruption.',
                ctaText: 'Update Payment',
                ctaLink: '/subscription/plans',
                dismissible: false,
            };
        }

        // Trial statuses
        if (isOnTrial.value) {
            const days = trialDaysRemaining.value;

            // Trial expires today
            if (days === 0) {
                return {
                    type: 'critical',
                    bgClass: 'bg-danger',
                    icon: 'ki-filled ki-time',
                    message: 'Your trial expires today! Upgrade now to keep access.',
                    ctaText: 'Upgrade Now',
                    ctaLink: '/subscription/plans',
                    dismissible: false,
                };
            }

            // Trial 1-3 days
            if (days >= 1 && days <= 3) {
                return {
                    type: 'urgent',
                    bgClass: 'bg-warning',
                    icon: 'ki-filled ki-time',
                    message: `Your trial expires in ${days} day${days === 1 ? '' : 's'}. Upgrade to keep full access.`,
                    ctaText: 'View Plans',
                    ctaLink: '/subscription/plans',
                    dismissible: true,
                };
            }

            // Trial 4-7 days
            if (days >= 4 && days <= 7) {
                return {
                    type: 'info',
                    bgClass: 'bg-primary',
                    icon: 'ki-filled ki-information-3',
                    message: `Your trial expires in ${days} days. Explore our plans to continue using all features.`,
                    ctaText: 'View Plans',
                    ctaLink: '/subscription/plans',
                    dismissible: true,
                };
            }
        }

        // Trial with status check (expired trial)
        if (status === 'trial' && !isOnTrial.value) {
            return {
                type: 'critical',
                bgClass: 'bg-danger',
                icon: 'ki-filled ki-shield-cross',
                message: 'Your trial has expired. Please upgrade to continue using all features.',
                ctaText: 'Upgrade Now',
                ctaLink: '/subscription/plans',
                dismissible: false,
            };
        }

        // Active or no warning needed
        return null;
    });

    // Should the banner be visible?
    const showBanner = computed(() => {
        if (!bannerConfig.value) return false;
        if (bannerConfig.value.dismissible && dismissed.value) return false;
        return true;
    });
</script>

<template>
    <div v-if="showBanner" class="w-full" :class="bannerConfig.bgClass">
        <div class="container-fixed">
            <div class="flex items-center justify-between gap-4 py-2.5">
                <!-- Icon + Message -->
                <div class="flex items-center gap-2.5">
                    <i class="text-white text-base" :class="bannerConfig.icon"></i>
                    <span class="text-white text-sm font-medium">
                        {{ bannerConfig.message }}
                    </span>
                </div>

                <!-- CTA + Dismiss -->
                <div class="flex items-center gap-2">
                    <Link
                        :href="bannerConfig.ctaLink"
                        class="btn btn-sm bg-white/20 hover:bg-white/30 text-white border-0 font-semibold text-xs shrink-0"
                    >
                        {{ bannerConfig.ctaText }}
                    </Link>

                    <button
                        v-if="bannerConfig.dismissible"
                        @click="dismiss"
                        class="btn btn-icon btn-sm bg-white/10 hover:bg-white/20 text-white border-0 shrink-0"
                        title="Dismiss"
                    >
                        <i class="ki-filled ki-cross text-sm"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
