# Page snapshot

```yaml
- generic [ref=e6]:
  - img "or" [ref=e9]
  - generic [ref=e11]:
    - heading "Sign in" [level=3] [ref=e12]
    - generic [ref=e13]:
      - generic [ref=e14]: Need an account?
      - link "Sign up" [ref=e15] [cursor=pointer]:
        - /url: /register
  - generic [ref=e16]:
    - generic [ref=e17]: Email
    - textbox "Your Email" [ref=e18]: abdulkadir.devworks@gmail.com
    - paragraph [ref=e20]: An error occurred. Please try again.
  - generic [ref=e21]:
    - generic [ref=e22]:
      - generic [ref=e23]: Password
      - link "Forgot Password?" [ref=e24] [cursor=pointer]:
        - /url: /forgot-password
    - generic [ref=e25]:
      - textbox "Enter Password" [ref=e26]
      - button " ":
        - generic:  
        - text:  
  - generic [ref=e27]:
    - checkbox "Remember me" [ref=e28]
    - generic [ref=e29]: Remember me
  - button "Sign In" [active] [ref=e30] [cursor=pointer]:
    - generic [ref=e31]: Sign In
```