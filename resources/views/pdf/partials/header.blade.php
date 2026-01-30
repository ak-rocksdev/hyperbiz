{{-- Company Header Partial --}}
<div class="document-header">
    <table>
        <tr>
            <td style="width: 50%; vertical-align: middle;">
                @if($company->logo && file_exists(public_path($company->logo)))
                    <img src="{{ public_path($company->logo) }}" alt="{{ $company->name }}" class="company-logo">
                @elseif(file_exists(public_path('assets/media/app/logo.png')))
                    <img src="{{ public_path('assets/media/app/logo.png') }}" alt="{{ $company->name ?? 'Company' }}" class="company-logo">
                @else
                    <div class="company-name" style="text-align: left;">{{ $company->name ?? 'HyperBiz' }}</div>
                @endif
            </td>
            <td style="width: 50%; vertical-align: middle;" class="company-info">
                <div class="company-name">{{ $company->name ?? 'HyperBiz' }}</div>
                <div class="company-details">
                    @if($company->address){{ $company->address }}<br>@endif
                    @if($company->phone)<i style="font-style: normal;">Phone:</i> {{ $company->phone }}<br>@endif
                    @if($company->email)<i style="font-style: normal;">Email:</i> {{ $company->email }}<br>@endif
                    @if($company->website){{ $company->website }}@endif
                </div>
            </td>
        </tr>
    </table>
</div>
