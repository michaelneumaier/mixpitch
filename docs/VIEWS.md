# Views (`resources/views/`)

This section documents the Blade views responsible for rendering the application's UI.

## Layouts

### Main Application Layout (`app.blade.php`)

**File:** `resources/views/components/layouts/app.blade.php`

**Purpose:** Serves as the primary HTML structure and layout wrapper for most authenticated application pages and potentially some public pages.

**Key Features & Technologies:**
*   **Structure:** Standard HTML5, `min-h-screen` flex layout pushing footer down.
*   **Styling:**
    *   Uses Vite for `resources/css/app.css` (likely includes Tailwind CSS).
    *   Utilizes DaisyUI theme (`data-theme="main"`).
    *   Includes custom CSS (`public/css/custom.css`, `public/css/homepage.css`, `public/css/star-rating.css`).
    *   Includes Google Fonts (`Inter`).
    *   Includes `@livewireStyles`.
    *   Basic Alpine.js `x-cloak` style.
*   **JavaScript:**
    *   Uses Vite for `resources/js/app.js` (likely includes Alpine.js).
    *   Includes `@livewireScripts()`.
    *   Includes `wavesurfer.js` globally.
    *   Includes jQuery, Popper.js, and potentially conflicting Bootstrap 4/5 JS versions.
    *   Supports page-specific scripts via `@yield('scripts')` and `@stack('scripts')`.
    *   Includes `<x-toaster-hub />` for notifications.
*   **Partials:**
    *   Includes navigation: `@include('components.layouts.navigation')`.
    *   Includes footer: `@include('components.layouts.footer')`.
*   **Content Sections:**
    *   Renders main page content via `$slot` (for Livewire v3 layout usage) or `@yield('content')` (for traditional `@extends`).
    *   Optionally displays a page header via the `$header` slot.

**Interaction:**
*   Provides the consistent shell (header, footer, base styles, global JS) for pages extending it or using it as a Livewire layout.
*   Loads necessary assets via Vite and direct links.
*   Integrates Livewire, Alpine.js, Wavesurfer.js, and Toaster.

### Navigation Partial (`navigation.blade.php`)

**File:** `resources/views/components/layouts/navigation.blade.php`

**Purpose:** Renders the main site navigation bar, included by the `app.blade.php` layout.

**Key Features:**
*   **Responsive:** Uses Alpine.js for mobile hamburger menu functionality.
*   **Styling:** Styled with Tailwind/DaisyUI, highlights active links based on current route.
*   **Content (Desktop):** Logo, Projects, Pricing, About links. Auth section shows NotificationList/AuthDropdown (Livewire) if logged in, or Login/Register links if guest.
*   **Content (Mobile):** Logo, hamburger. Expanded menu shows Projects, Pricing, About, Dashboard. Conditional links for Admin Dashboard and Profile Setup. Auth section shows user info, profile/settings links, logout button if logged in, or Login/Register buttons if guest.
*   **Livewire Integration:** Embeds `NotificationList` and `AuthDropdown` components.
*   **Authorization:** Uses `Auth::check()`, `Auth::user()->canAccessPanel()`, `Auth::user()->hasCompletedProfile()` for conditional display.

**Interaction:** Provides consistent navigation across the site. Integrates Livewire components for dynamic elements. Adapts based on authentication status and user permissions/profile completion.

### Footer Partial (`footer.blade.php`)

**File:** `resources/views/components/layouts/footer.blade.php`

**Purpose:** Renders the site footer, included by the `app.blade.php` layout.

**Key Features:**
*   **Structure:** Multi-column layout for Quick Links, Social Media, Newsletter signup.
*   **Styling:** Basic Tailwind styling.
*   **Content:** Static links (some placeholders), social media icons (Font Awesome, placeholder links), newsletter form (placeholder action), copyright notice.

**Interaction:** Provides static footer content and links.

*(Further view analysis pending...)*

## Custom Blade Components (`resources/views/components/`)

### Invoice Details Component (`invoice-details.blade.php`)

**File:** `resources/views/components/invoice-details.blade.php`

**Purpose:** Renders a detailed view of a single invoice, likely using data fetched from Stripe.

**Usage:** Expected to be used like `<x-invoice-details :invoice="$invoiceObject" :view-all-url="route(...)" />`

**Input Props:**
*   `$invoice`: An object containing invoice data (ID/number, status, date, total, description, line items). Structure suggests compatibility with Stripe API responses or formatted objects from `InvoiceService`.
*   `$viewAllUrl` (optional): A URL string for a 'View all invoices' link.

**Key Features:**
*   Displays invoice number, paid/unpaid status badge, date, and total amount.
*   Lists line items from `$invoice->lines->data` or shows a single summary line.
*   Provides a 'Download PDF' link to `route('billing.invoice.download', $invoice->id)`.
*   Optionally provides a 'View all invoices' link.
*   Styled with Tailwind CSS.

**Interaction:** Primarily a display component formatting invoice data. Provides navigation links for downloading and viewing other invoices.

*(Further view analysis pending...)*

### Update Pitch Status Buttons Component (`update-pitch-status.blade.php`)

**File:** `resources/views/components/update-pitch-status.blade.php`

**Purpose:** Renders action buttons for changing a pitch's status based on its current state.
**Note: This component appears highly problematic and likely redundant/deprecated.** Its logic seems to belong within the `UpdatePitchStatus` Livewire component's view (`resources/views/livewire/pitch/component/update-pitch-status.blade.php`). This Blade component bypasses the Livewire component's actions and service layer calls in many cases.

**Usage:** Likely intended usage `<x-update-pitch-status :pitch="$pitch" />`

**Input Props:**
*   `$pitch`: The `Pitch` model.

**Key Features & Issues:**
*   **Conditional Logic:** Uses extensive `@if/@elseif` based on `$pitch->status` to determine which actions/buttons to show.
*   **Direct Form Submissions (Issue):** For several statuses (Pending, In Progress, Approved, Denied, Revisions Requested, Completed), it renders HTML forms that POST directly to controller routes (`projects.pitches.change-status`, `projects.pitches.return-to-approved`). This bypasses the `UpdatePitchStatus` Livewire component and the `PitchWorkflowService`, potentially leading to inconsistent state management and authorization issues.
*   **JavaScript Modal Triggers (Partial Alignment):** For the `READY_FOR_REVIEW` status, it renders buttons that trigger JavaScript functions (`openApproveModal`, etc.) presumably defined elsewhere to interact with modals (`<x-pitch-action-modals />`). This aligns partially with the Livewire component's modal interaction but still passes controller routes directly to JS instead of using Livewire actions.
*   **Includes Modals:** Includes `<x-pitch-action-modals />`.

**Interaction:** Renders conditional buttons/forms. Interacts via direct form POSTs or JavaScript calls, largely bypassing the corresponding Livewire component. Includes a modal component.

**Recommendation:** This component should be reviewed and likely removed. Its logic needs to be consolidated within the `UpdatePitchStatus` Livewire component and its associated view to ensure consistent use of the `PitchWorkflowService` and proper state management.

*(Further view analysis pending...)*

### Pitch Action Modals Component (`pitch-action-modals.blade.php`)

**File:** `resources/views/components/pitch-action-modals.blade.php`

**Purpose:** Defines the HTML structure for modals used for Approve, Deny, and Request Revisions actions on pitches.

**Usage:** Included by other components, e.g., `<x-pitch-action-modals />` (as seen in `update-pitch-status.blade.php`).

**Input Props:** None.

**Key Features:**
*   **Structure:** Contains three distinct modal sections (`#approveModal`, `#denyModal`, `#revisionsModal`), initially hidden.
*   **Forms:** Each modal contains a basic, hidden HTML form (`#approveForm`, etc.) with only `@csrf`. JavaScript is expected to set the `action` URL and potentially add data before submission.
*   **Inputs:** Provides textareas for denial reason (`#denyReason`) and revision requests (`#revisionsRequested`).
*   **Buttons:** Includes Cancel buttons (`onclick="closeModal(...)"`) and confirmation buttons (`#approveSubmitBtn`, etc.) presumably handled by JavaScript.
*   **JavaScript Dependency:** Includes `<script src="{{ asset('js/pitch-modals.js') }}"></script>`, indicating all modal interaction logic (opening, closing, submitting the hidden forms) resides in this external JS file.
*   **Styling:** Uses Tailwind CSS.

**Interaction:** Provides the static modal HTML. Relies entirely on `pitch-modals.js` (and functions like `openApproveModal`, `closeModal`) for display logic and form submission, targeting controller routes directly. This approach is less integrated with Livewire/Alpine compared to using Livewire components for modal state and actions.

**Note:** The reliance on external JS to manipulate and submit hidden forms directly to controller routes is part of the potentially problematic pattern identified in `components/update-pitch-status.blade.php`.

*(Further view analysis pending...)*

### Pitch Terms Modal Component (`pitch-terms-modal.blade.php`)

**File:** `resources/views/components/pitch-terms-modal.blade.php`

**Purpose:** Displays a modal with terms highlights that must be agreed to before initiating a pitch creation request.

**Usage:** Likely included on project detail pages, e.g., `<x-pitch-terms-modal :project="$project" />`. Triggered via JavaScript (`openPitchTermsModal()`).

**Input Props:**
*   `$project`: The `Project` model the user wants to pitch for.

**Key Features:**
*   **Structure:** Defines a modal (`#pitch-terms-modal`) with header, body (terms highlights, full terms link, agreement checkbox), and footer.
*   **Form:** Contains a hidden HTML form (`#pitch-create-form`) targeting `route('projects.pitches.store')` via POST. Includes `@csrf` and `$project->id`.
*   **Agreement:** Requires user to check an 'Agree to Terms' checkbox (`#agree_terms`).
*   **JavaScript:** Includes inline JS defining global functions (`openPitchTermsModal`, `closePitchTermsModal`, `submitPitchForm`) for modal control and form submission. `submitPitchForm` validates checkbox before submitting.
*   **Styling:** Uses Tailwind CSS.

**Interaction:** Provides the UI flow for agreeing to terms before creating a pitch. Uses inline JavaScript for modal display and conditional form submission. Submits directly to a controller route upon confirmation.

*(Further view analysis pending...)*

### User Link Component (`user-link.blade.php`)

**File:** `resources/views/components/user-link.blade.php`

**Purpose:** Renders a user's name, providing a link to their public profile if they have a username.

**Usage:** `<x-user-link :user="$userObject" />`

**Input Props:**
*   `$user`: The `User` model instance.

**Key Features:**
*   Conditionally renders an anchor tag linking to `route('profile.username', ['username' => $user->username])` if `$user->username` is set.
*   Displays the `$user->name` as the link text or plain text.
*   Basic Tailwind link styling.

**Interaction:** Displays user name, conditionally linking to their profile.

*(Further view analysis pending...)*

### Pitch Status Banner Component (`pitch-status.blade.php`)

**File:** `resources/views/components/pitch-status.blade.php`

**Purpose:** Renders a prominent banner displaying a formatted pitch status with dynamic background and text colors.

**Usage:** Likely used via `@include('components.pitch-status', ['status' => $statusString, 'bgColor' => $tailwindBgClass, 'textColor' => $tailwindTextClass])`

**Input Props (Implicit):**
*   `$status`: The pitch status string (e.g., 'ready_for_review').
*   `$bgColor`: A Tailwind background color class (e.g., 'bg-blue-100').
*   `$textColor`: A Tailwind text color class (e.g., 'text-blue-800').

**Key Features:**
*   Formats the `$status` string using `ucwords(str_replace('_', ' ', $status))` for display.
*   Dynamically applies background and text color classes passed via `$bgColor` and `$textColor` using `@apply`.
*   Styled with Tailwind CSS.

**Interaction:** Purely a display component for showing status in a styled banner.

*(Further view analysis pending...)*

### Project Status Button Component (`project-status-button.blade.php`)

**File:** `resources/views/components/project-status-button.blade.php`

**Purpose:** Renders a styled badge or indicator for a project's status, including a relevant icon.

**Usage:** Likely used via `@include('components.project-status-button', ['status' => $project->status, 'type' => 'inline'])` or within Blade component context.

**Input Props (Implicit):**
*   `$status`: The project status string ('unpublished', 'open', 'review', 'completed', 'closed').
*   `$type`: Controls styling variations (e.g., 'top-right' for specific positioning/rounding, defaults to 'inline' styling).

**Key Features:**
*   **Status Mapping:** Uses a PHP `@switch` statement to map project `$status` strings to specific Tailwind CSS classes (`$colorClass`) and Font Awesome icon classes (`$iconClass`).
*   **Custom Colors:** Uses custom color names (e.g., `bg-statusOpen`, `shadow-statusOpen`) presumably defined in Tailwind config or CSS.
*   **Conditional Styling:** Applies different `border-radius` styles based on the `$type` variable.
*   **Display:** Renders a `<span>` containing the icon and the raw `$status` string.
*   **Styling:** Uses Tailwind CSS and Font Awesome.

**Interaction:** Purely a display component for project status indication.

*(Further view analysis pending...)*

### Invoice List View (`invoices.blade.php`)

**File:** `resources/views/billing/invoices.blade.php`

**Purpose:** Displays a paginated list of all the user's invoices.

**Layout:** Uses `x-app-layout` with a header slot ("Billing History") and a back link.

**Data:** Expects an `$invoices` collection (likely paginated Cashier Invoice objects). Uses `session`.

**Structure:**
*   Displays session messages.
*   Shows an empty state if no invoices exist.
*   Displays invoices in a table: Number, Date, Amount, Status (Paid/Unpaid), Type (Pitch Payment/Standard), Actions (View/Download).
*   "Type" column checks metadata for `pitch_id` and links to the project if applicable.
*   Provides "View" and "Download" links per invoice.

**Interaction:** Displays invoice list. Provides links to view/download invoices and navigate to related projects. Assumes pagination if data is paginated.

*(Further view analysis pending...)*

### Billing Diagnostic View (`diagnostic.blade.php`)

**File:** `resources/views/billing/diagnostic.blade.php`

**Purpose:** Developer/admin tool to diagnose invoice synchronization issues by comparing raw Stripe invoice data with Cashier's local count.

**Layout:** Uses `x-app-layout` with a header slot ("Invoice Diagnostic") and a back link.

**Data:** Expects `$stripeId`, `$cashierInvoiceCount`, `$rawInvoiceData` (array of raw Stripe invoices).

**Structure:**
*   Displays Stripe Customer ID, Cashier invoice count, raw Stripe invoice count.
*   Shows a table of raw invoice data fetched directly from Stripe.
*   Provides a list of debugging steps, including suggesting an Artisan command (`stripe:sync-invoices --all`).
*   Includes a "Return to Billing" link.

**Interaction:** Static display of diagnostic information and debugging steps.

*(Further view analysis pending...)*

### Payment Methods Management View (`payment-methods.blade.php`)

**File:** `resources/views/billing/payment-methods.blade.php`

**Purpose:** Allows users to view all saved payment methods, set a default, add new ones, and remove existing ones.

**Layout:** Uses `x-app-layout` with a header slot ("Payment Methods").

**Data:** Expects `$paymentMethods` (collection), `$defaultPaymentMethod`, `$intent` (Stripe SetupIntent). Uses `session`.

**Structure:**
*   Displays session messages.
*   "Add New Card" button toggles a hidden Stripe Elements form.
*   Lists existing payment methods with icon, brand, last 4, expiry.
*   Highlights the default method.
*   Provides "Make Default" button (POST form) for non-default methods.
*   Provides "Remove" button (DELETE form with JS confirm) for all methods.
*   Shows empty state message.
*   Footer links to Billing overview and Stripe Portal.

**JavaScript (`@push('scripts')`):**
*   Includes Stripe.js.
*   Initializes Stripe, mounts Card Element, handles errors.
*   Handles add card form submission using `confirmCardSetup` with SetupIntent, then submits to backend.
*   Toggles add card form visibility.

**Interaction:** Manages multiple payment methods, integrates Stripe Elements for adding cards, uses forms for setting default/removing.

*(Further view analysis pending...)*

### Checkout View (`checkout.blade.php`)

**File:** `resources/views/billing/checkout.blade.php`

**Purpose:** Provides a dedicated page for handling payments, likely for subscriptions or other purchases initiated via Cashier's `checkout`. Focuses solely on collecting payment details.

**Layout:** Uses `x-app-layout` with a header slot ("Checkout").

**Data:** Expects `$intent` (Stripe SetupIntent) and potentially `$priceId`. Uses `session`.

**Structure:**
*   Displays session messages.
*   Contains a single form POSTing to `billing.payment.process`.
*   Includes hidden `price_id` (if provided).
*   Includes Stripe Card Element (`#card-element`) and error display (`#card-errors`).
*   Submit button holds SetupIntent secret.
*   Provides a "Back to Billing" link.

**JavaScript (`@push('scripts')`):**
*   Includes Stripe.js.
*   Initializes Stripe, mounts Card Element, handles errors.
*   Handles form submission using `confirmCardSetup` with SetupIntent, then submits payment method ID and price ID to backend.

**Interaction:** Streamlined checkout page using Stripe Elements and SetupIntents to capture card details for payment processing.

*(Further view analysis pending...)*

### Detailed Invoice View (`invoice-details.blade.php` - Potentially Redundant)

**File:** `resources/views/billing/invoice-details.blade.php`

**Note:** This is a **full page view**, not the component `components/invoice-details.blade.php`. It appears redundant and potentially unused, as `invoice-show.blade.php` uses the component.

**Purpose:** Displays detailed invoice information, including line items, summary, payment method, and billing address.

**Layout:** Uses `x-app-layout` with header including back/download links.

**Data:** Expects `$invoice` (Cashier Invoice object). Uses `session`.

**Structure:**
*   Displays session messages.
*   Shows Invoice Header (Number, Date, Status).
*   Shows Summary (Subtotal, Tax, Discount, Total).
*   Shows Line Items table.
*   **Contains logic within the view** to fetch related Stripe PaymentMethod (for paid invoices) and Customer objects using the Stripe SDK.
*   Displays Payment Method details (Card Brand, Last 4, Expiry).
*   Displays Billing Details (Name, Email, Address).

**Interaction:** Static display of detailed invoice info. Includes logic within the view to fetch related Stripe data.

**Potential Issues:**
*   Likely redundant due to `invoice-show.blade.php` using the `x-invoice-details` component.
*   Contains business logic (Stripe SDK calls) within the view, violating separation of concerns.

*(Further view analysis pending...)*

## Email Views (`resources/views/emails/`)

### Email Test Form View (`test-form.blade.php`)

**File:** `resources/views/emails/test-form.blade.php`

**Purpose:** Provides a simple form for sending a test email (likely corresponds to `EmailController::showTestForm`).

**Layout:** Uses `x-app-layout` with a "Test Email" header.

**Data:** Uses `session` and `@error` directive.

**Structure:**
*   Displays session success/error messages.
*   Simple form POSTing to `email.test.send`.
*   Includes `@csrf`.
*   Input field for recipient email (`name="email"`) with validation error display.
*   "Send Test Email" submit button.

**Interaction:** User enters email and submits form to trigger sending of a predefined test email via the backend.

*(Further view analysis pending...)*

### Test Email Template (`test.blade.php`)

**File:** `resources/views/emails/test.blade.php`

**Purpose:** Defines the HTML structure and content for the test email sent via the `TestMail` Mailable.

**Layout:** Standalone HTML file with basic inline CSS. Does not use standard Laravel email components/markdown.

**Data:** Uses `date('Y')`.

**Structure:**
*   Simple centered container.
*   Static heading and text confirming email configuration (mentioning SES) is working.
*   Basic footer with copyright.

**Interaction:** Static email content.

*(Further view analysis pending...)*

### Jetstream Team Invitation Email (`team-invitation.blade.php`)

**File:** `resources/views/emails/team-invitation.blade.php`

**Purpose:** Standard Jetstream email template sent when a user is invited to a team.

**Layout:** Uses Laravel Markdown Mail components (`mail::message`, `mail::button`).

**Data:** Expects `$invitation` (Jetstream object) and `$acceptUrl`. Uses Fortify features check.

**Structure:**
*   States which team the user is invited to.
*   Conditionally includes a "Create Account" button if registration is enabled.
*   Includes an "Accept Invitation" button linking to `$acceptUrl`.
*   Includes discard instructions.

**Interaction:** Provides buttons to create account/accept invitation.

*(Further view analysis pending...)*

### Payment Receipt Email (`payment/receipt.blade.php`)

**File:** `resources/views/emails/payment/receipt.blade.php`

**Purpose:** Email template sent to both project owner and pitch creator upon successful payment or project completion (for free projects).

**Layout:** Standalone HTML file with basic inline CSS.

**Data:** Expects `$isFreeProject`, `$recipientName`, `$recipientType`, `$project`, `$pitch`, `$paymentDate`, `$invoiceId`, `$amount`.

**Structure:**
*   Mixpitch logo and title.
*   Greeting and text confirming payment/completion, tailored to recipient.
*   Summary box: Project Name, Pitch ID, Date, Invoice ID (if applicable), Amount/"Free Project".
*   Concluding message tailored to recipient.
*   Button linking to the web receipt view (`projects.pitches.payment.receipt`).
*   Standard footer.

**Interaction:** Confirmation email with link to web receipt.

*(Further view analysis pending...)*

## Filament Views (`resources/views/filament/`)

**Purpose:** Contains Blade views used by the Filament Admin Panel.

**Structure:** Organized into subdirectories like `pages`, `resources`, `widgets`, etc., mirroring Filament concepts.

**Analysis:** Views generally follow standard Filament patterns:
*   Use Filament layout components (e.g., `<x-filament::page>`).
*   Render widgets using `<x-filament-widgets::widgets />` based on configurations in PHP classes.
*   Embed Livewire components for specific functionality (e.g., `<livewire:email-test-form />` in `test-email-page.blade.php`).
*   Minimal custom Blade logic; complexity resides in corresponding `app/Filament/` PHP classes and embedded Livewire components.

**(Skipping detailed analysis of individual files as they follow standard Filament structure).**

*(Further view analysis pending...)*

## Vendor Views (`resources/views/vendor/`)

This directory is **empty**. No third-party package views have been published for customization.

*(Further view analysis pending...)*

## User Profile Views (`resources/views/user-profile/` & `resources/views/profile/`)

### Public Profile View (`user-profile/show.blade.php`)

**File:** `resources/views/user-profile/show.blade.php`

**Purpose:** Displays the public profile page for a given user.

**Layout:** Uses `x-app-layout`.

**Data:** Expects `$user`, `$canEdit` (boolean), `$projects` (collection), `$completedPitches` (collection).

**Structure:**
*   **Profile Header:** Avatar, Name, Username, Headline, Location, Website. Conditional "Edit Profile" button. Social media links, Tipjar link.
*   **Main Content Grid (2 columns):**
    *   **Left:** About (Bio, conditional Tipjar button), Skills/Equipment/Specialties (displayed as badges).
    *   **Right:** User's public Projects grid, Completed Pitches list.

**Interaction:** Displays profile info, projects, completed work. Includes external links (social, website, tipjar) and edit link for owner.

*(Further view analysis pending...)*

### Profile Edit View (Livewire) (`user-profile/edit-livewire.blade.php`)

**File:** `resources/views/user-profile/edit-livewire.blade.php`

**Purpose:** Container view that loads the `UserProfileEdit` Livewire component for editing the authenticated user's profile.

**Layout:** Uses `x-app-layout`.

**Data:** None passed directly; Livewire component fetches authenticated user.

**Structure:** Includes `<livewire:user-profile-edit />`.

**Interaction:** All logic handled by the embedded `UserProfileEdit` Livewire component.

*(Further view analysis pending...)*

### Profile Edit View (Standard Form - Potentially Redundant) (`user-profile/edit.blade.php`)

**File:** `resources/views/user-profile/edit.blade.php`

**Note:** This appears to be a standard HTML form implementation for profile editing, likely redundant due to the Livewire version (`user-profile/edit-livewire.blade.php` loading `UserProfileEdit`).

**Purpose:** Allows authenticated user to edit profile details via a standard form.

**Layout:** Uses `x-app-layout` with "Edit Profile" header and conditional public profile link.

**Data:** Expects `$user`. Uses `session`, `@error`, `old()`.

**Structure:**
*   Displays session messages and username setup prompt.
*   Standard HTML form POSTing to `profile.update` (PUT method).
*   Inputs for Username, Bio, Website, Location.
*   Inputs for Social Links (Twitter, Instagram, SoundCloud, Spotify, YouTube) using array format (`social_links[platform]`). Attempts to use `$this->getSocialUsername()` for pre-filling, suggesting it might have been intended for a Livewire component or requires a helper method.
*   "Save" button.

**Interaction:** Standard HTML form submission to `UserProfileController::update`.

**Redundancy:** Likely redundant/unused in favor of the Livewire implementation.

*(Further view analysis pending...)*

### Jetstream Profile Views (`resources/views/profile/`)

**Purpose:** Contains standard Jetstream views for user profile management (update info, password, 2FA, browser sessions, delete account).

**Structure:** The main view (`show.blade.php`) uses `x-app-layout` and conditionally includes various `@livewire('profile.*')` components based on enabled Jetstream/Fortify features. Other files in the directory are the Blade views for these embedded Livewire components.

**Interaction:** All logic is handled by the embedded Jetstream Livewire components.

**(Skipping detailed analysis of individual files as they are standard Jetstream components).**

*(Further view analysis pending...)*

## Pitch Files Views (`resources/views/pitch-files/`)

### Pitch File Show View (`show.blade.php`)

**File:** `resources/views/pitch-files/show.blade.php`

**Purpose:** Container view to display a single pitch file using the `PitchFilePlayer` Livewire component.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$file` (PitchFile model).

**Structure:**
*   Includes `<livewire:pitch-file-player :file="$file" />`.
*   Pushes basic CSS for waveform loading/timeline.
*   Pushes Wavesurfer.js library.

**Interaction:** All logic handled by the embedded `PitchFilePlayer` Livewire component.

*(Further view analysis pending...)*

## Authentication Views (`resources/views/auth/`)

**Purpose:** Contains standard Laravel Fortify views for handling authentication flows (login, registration, password reset, two-factor auth, etc.).

**Structure:** Views use `x-guest-layout` and standard Jetstream/Fortify Blade components (e.g., `x-authentication-card`). They correspond to Fortify's backend routes and actions.

**Interaction:** Standard HTML forms submitting to Fortify controllers/actions.

**(Skipping detailed analysis of individual files as they are standard Fortify views).**

*(Further view analysis pending...)*

## API Token Views (`resources/views/api/`)

**Purpose:** Contains standard Jetstream views for API token management.

**Structure:** The main view (`index.blade.php`) uses `x-app-layout` and includes the `@livewire('api.api-token-manager')` component. The other file (`api-token-manager.blade.php`) is the view for that Livewire component.

**Interaction:** All logic handled by the embedded Jetstream Livewire component.

**(Skipping detailed analysis as they are standard Jetstream components).**

*(Further view analysis pending...)*

## Projects Views (`resources/views/projects/`)

### Public Project Show View (`project.blade.php`)

**File:** `resources/views/projects/project.blade.php`

**Purpose:** Displays the public detail page for a single project.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project` (with relations), `$canPitch` (boolean), `$userPitch` (Pitch model or null). Uses `Auth`.

**Structure:**
*   **Header Card:** Project image (Alpine lightbox), preview track audio player (Livewire), Project Name, Artist, Owner (`x-user-link`), Status (`x-project-status-button`), Type, Deadline. Conditional buttons (Manage Project / Start Pitch / View Pitch).
*   **Details Section:** Collaboration badges, Budget, Description, Notes.

**Interaction:** Displays project details. Conditional actions for owner (Manage) or others (Start/View Pitch). Pitch initiation uses JS modal (`openPitchTermsModal()`). Includes audio player and image lightbox.

*(Further view analysis pending...)*

### Project Edit View (Standard Form - Potentially Redundant) (`edit.blade.php`)

**File:** `resources/views/projects/edit.blade.php`

**Note:** Likely redundant due to the `ManageProject` Livewire component handling project editing and file management.

**Purpose:** Allows editing project details and managing files via standard HTML forms.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project` (with relations). Uses `old()`.

**Structure:**
*   **Header:** Displays project image (clickable for upload via JS), inputs for Name, Description, Genre, Status.
*   **Form:** Standard HTML form POSTing to `projects.update` (PUT method), `enctype="multipart/form-data"`.
*   **File Management:** Lists existing files with Delete buttons (individual DELETE forms triggered by JS). Links to a separate `projects.createStep2` route for uploads.

**JavaScript:** Includes `loadFile` for image preview and event listener for delete buttons.

**Interaction:** Standard form submission for edits. File deletion via separate forms triggered by JS. Links to potentially deprecated upload step.

**Redundancy:** Likely redundant/unused in favor of the `ManageProject` Livewire implementation.

### Project Index View (`index.blade.php` - Livewire Wrapper)

**File:** `resources/views/projects/index.blade.php`

**Purpose:** Container view for the public project listing.

**Layout:** Extends `components.layouts.app`.

**Structure:** Includes `<livewire:projects-component />`. Assumes the listing logic is handled by this Livewire component.

### Project Upload (Step 2) View (`create_step2.blade.php` - Likely Deprecated)

**File:** `resources/views/projects/create_step2.blade.php`

**Purpose:** Provides a Dropzone.js interface for uploading files as part of a multi-step project creation/edit process.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project`.

**Structure:** Includes Dropzone CSS/JS. Contains Dropzone form POSTing to `projects.storeStep2`. Includes Back/Finish links.

**JavaScript:** Initializes Dropzone.

**Redundancy:** Uses Dropzone and separate upload step, contrasting with integrated S3 uploads in `ManageProject`. Highly likely deprecated.

### Upload Project View (`upload-project.blade.php` - TrackController related)

**File:** `resources/views/projects/upload-project.blade.php`

**Purpose:** Container view, likely related to the `TrackController` functionality.

**Layout:** Extends `components.layouts.app`.

**Structure:** Includes `<livewire:upload-project-component />`. Logic is assumed to be in this Livewire component.

*(Further view analysis pending...)*

## Components (`resources/views/components/`)

### Invoice Details Component (`invoice-details.blade.php`)

**File:** `resources/views/components/invoice-details.blade.php`

**Purpose:** Renders a detailed view of a single invoice, likely using data fetched from Stripe.

**Usage:** Expected to be used like `<x-invoice-details :invoice="$invoiceObject" :view-all-url="route(...)" />`

**Input Props:**
*   `$invoice`: An object containing invoice data (ID/number, status, date, total, description, line items). Structure suggests compatibility with Stripe API responses or formatted objects from `InvoiceService`.
*   `$viewAllUrl` (optional): A URL string for a 'View all invoices' link.

**Key Features:**
*   Displays invoice number, paid/unpaid status badge, date, and total amount.
*   Lists line items from `$invoice->lines->data` or shows a single summary line.
*   Provides a 'Download PDF' link to `route('billing.invoice.download', $invoice->id)`.
*   Optionally provides a 'View all invoices' link.
*   Styled with Tailwind CSS.

**Interaction:** Primarily a display component formatting invoice data. Provides navigation links for downloading and viewing other invoices.

*(Further view analysis pending...)*

### Update Pitch Status Buttons Component (`update-pitch-status.blade.php`)

**File:** `resources/views/components/update-pitch-status.blade.php`

**Purpose:** Renders action buttons for changing a pitch's status based on its current state.
**Note: This component appears highly problematic and likely redundant/deprecated.** Its logic seems to belong within the `UpdatePitchStatus` Livewire component's view (`resources/views/livewire/pitch/component/update-pitch-status.blade.php`). This Blade component bypasses the Livewire component's actions and service layer calls in many cases.

**Usage:** Likely intended usage `<x-update-pitch-status :pitch="$pitch" />`

**Input Props:**
*   `$pitch`: The `Pitch` model.

**Key Features & Issues:**
*   **Conditional Logic:** Uses extensive `@if/@elseif` based on `$pitch->status` to determine which actions/buttons to show.
*   **Direct Form Submissions (Issue):** For several statuses (Pending, In Progress, Approved, Denied, Revisions Requested, Completed), it renders HTML forms that POST directly to controller routes (`projects.pitches.change-status`, `projects.pitches.return-to-approved`). This bypasses the `UpdatePitchStatus` Livewire component and the `PitchWorkflowService`, potentially leading to inconsistent state management and authorization issues.
*   **JavaScript Modal Triggers (Partial Alignment):** For the `READY_FOR_REVIEW` status, it renders buttons that trigger JavaScript functions (`openApproveModal`, etc.) presumably defined elsewhere to interact with modals (`<x-pitch-action-modals />`). This aligns partially with the Livewire component's modal interaction but still passes controller routes directly to JS instead of using Livewire actions.
*   **Includes Modals:** Includes `<x-pitch-action-modals />`.

**Interaction:** Renders conditional buttons/forms. Interacts via direct form POSTs or JavaScript calls, largely bypassing the corresponding Livewire component. Includes a modal component.

**Recommendation:** This component should be reviewed and likely removed. Its logic needs to be consolidated within the `UpdatePitchStatus` Livewire component and its associated view to ensure consistent use of the `PitchWorkflowService` and proper state management.

*(Further view analysis pending...)*

### Pitch Action Modals Component (`pitch-action-modals.blade.php`)

**File:** `resources/views/components/pitch-action-modals.blade.php`

**Purpose:** Defines the HTML structure for modals used for Approve, Deny, and Request Revisions actions on pitches.

**Usage:** Included by other components, e.g., `<x-pitch-action-modals />` (as seen in `update-pitch-status.blade.php`).

**Input Props:** None.

**Key Features:**
*   **Structure:** Contains three distinct modal sections (`#approveModal`, `#denyModal`, `#revisionsModal`), initially hidden.
*   **Forms:** Each modal contains a basic, hidden HTML form (`#approveForm`, etc.) with only `@csrf`. JavaScript is expected to set the `action` URL and potentially add data before submission.
*   **Inputs:** Provides textareas for denial reason (`#denyReason`) and revision requests (`#revisionsRequested`).
*   **Buttons:** Includes Cancel buttons (`onclick="closeModal(...)"`) and confirmation buttons (`#approveSubmitBtn`, etc.) presumably handled by JavaScript.
*   **JavaScript Dependency:** Includes `<script src="{{ asset('js/pitch-modals.js') }}"></script>`, indicating all modal interaction logic (opening, closing, submitting the hidden forms) resides in this external JS file.
*   **Styling:** Uses Tailwind CSS.

**Interaction:** Provides the static modal HTML. Relies entirely on `pitch-modals.js` (and functions like `openApproveModal`, `closeModal`) for display logic and form submission, targeting controller routes directly. This approach is less integrated with Livewire/Alpine compared to using Livewire components for modal state and actions.

**Note:** The reliance on external JS to manipulate and submit hidden forms directly to controller routes is part of the potentially problematic pattern identified in `components/update-pitch-status.blade.php`.

*(Further view analysis pending...)*

### Pitch Terms Modal Component (`pitch-terms-modal.blade.php`)

**File:** `resources/views/components/pitch-terms-modal.blade.php`

**Purpose:** Displays a modal with terms highlights that must be agreed to before initiating a pitch creation request.

**Usage:** Likely included on project detail pages, e.g., `<x-pitch-terms-modal :project="$project" />`. Triggered via JavaScript (`openPitchTermsModal()`).

**Input Props:**
*   `$project`: The `Project` model the user wants to pitch for.

**Key Features:**
*   **Structure:** Defines a modal (`#pitch-terms-modal`) with header, body (terms highlights, full terms link, agreement checkbox), and footer.
*   **Form:** Contains a hidden HTML form (`#pitch-create-form`) targeting `route('projects.pitches.store')` via POST. Includes `@csrf` and `$project->id`.
*   **Agreement:** Requires user to check an 'Agree to Terms' checkbox (`#agree_terms`).
*   **JavaScript:** Includes inline JS defining global functions (`openPitchTermsModal`, `closePitchTermsModal`, `submitPitchForm`) for modal control and form submission. `submitPitchForm` validates checkbox before submitting.
*   **Styling:** Uses Tailwind CSS.

**Interaction:** Provides the UI flow for agreeing to terms before creating a pitch. Uses inline JavaScript for modal display and conditional form submission. Submits directly to a controller route upon confirmation.

*(Further view analysis pending...)*

### User Link Component (`user-link.blade.php`)

**File:** `resources/views/components/user-link.blade.php`

**Purpose:** Renders a user's name, providing a link to their public profile if they have a username.

**Usage:** `<x-user-link :user="$userObject" />`

**Input Props:**
*   `$user`: The `User` model instance.

**Key Features:**
*   Conditionally renders an anchor tag linking to `route('profile.username', ['username' => $user->username])` if `$user->username` is set.
*   Displays the `$user->name` as the link text or plain text.
*   Basic Tailwind link styling.

**Interaction:** Displays user name, conditionally linking to their profile.

*(Further view analysis pending...)*

### Pitch Status Banner Component (`pitch-status.blade.php`)

**File:** `resources/views/components/pitch-status.blade.php`

**Purpose:** Renders a prominent banner displaying a formatted pitch status with dynamic background and text colors.

**Usage:** Likely used via `@include('components.pitch-status', ['status' => $statusString, 'bgColor' => $tailwindBgClass, 'textColor' => $tailwindTextClass])`

**Input Props (Implicit):**
*   `$status`: The pitch status string (e.g., 'ready_for_review').
*   `$bgColor`: A Tailwind background color class (e.g., 'bg-blue-100').
*   `$textColor`: A Tailwind text color class (e.g., 'text-blue-800').

**Key Features:**
*   Formats the `$status` string using `ucwords(str_replace('_', ' ', $status))` for display.
*   Dynamically applies background and text color classes passed via `$bgColor` and `$textColor` using `@apply`.
*   Styled with Tailwind CSS.

**Interaction:** Purely a display component for showing status in a styled banner.

*(Further view analysis pending...)*

### Project Status Button Component (`project-status-button.blade.php`)

**File:** `resources/views/components/project-status-button.blade.php`

**Purpose:** Renders a styled badge or indicator for a project's status, including a relevant icon.

**Usage:** Likely used via `@include('components.project-status-button', ['status' => $project->status, 'type' => 'inline'])` or within Blade component context.

**Input Props (Implicit):**
*   `$status`: The project status string ('unpublished', 'open', 'review', 'completed', 'closed').
*   `$type`: Controls styling variations (e.g., 'top-right' for specific positioning/rounding, defaults to 'inline' styling).

**Key Features:**
*   **Status Mapping:** Uses a PHP `@switch` statement to map project `$status` strings to specific Tailwind CSS classes (`$colorClass`) and Font Awesome icon classes (`$iconClass`).
*   **Custom Colors:** Uses custom color names (e.g., `bg-statusOpen`, `shadow-statusOpen`) presumably defined in Tailwind config or CSS.
*   **Conditional Styling:** Applies different `border-radius` styles based on the `$type` variable.
*   **Display:** Renders a `<span>` containing the icon and the raw `$status` string.
*   **Styling:** Uses Tailwind CSS and Font Awesome.

**Interaction:** Purely a display component for project status indication.

*(Further view analysis pending...)*

### Invoice List View (`invoices.blade.php`)

**File:** `resources/views/billing/invoices.blade.php`

**Purpose:** Displays a paginated list of all the user's invoices.

**Layout:** Uses `x-app-layout` with a header slot ("Billing History") and a back link.

**Data:** Expects an `$invoices` collection (likely paginated Cashier Invoice objects). Uses `session`.

**Structure:**
*   Displays session messages.
*   Shows an empty state if no invoices exist.
*   Displays invoices in a table: Number, Date, Amount, Status (Paid/Unpaid), Type (Pitch Payment/Standard), Actions (View/Download).
*   "Type" column checks metadata for `pitch_id` and links to the project if applicable.
*   Provides "View" and "Download" links per invoice.

**Interaction:** Displays invoice list. Provides links to view/download invoices and navigate to related projects. Assumes pagination if data is paginated.

*(Further view analysis pending...)*

### Billing Diagnostic View (`diagnostic.blade.php`)

**File:** `resources/views/billing/diagnostic.blade.php`

**Purpose:** Developer/admin tool to diagnose invoice synchronization issues by comparing raw Stripe invoice data with Cashier's local count.

**Layout:** Uses `x-app-layout` with a header slot ("Invoice Diagnostic") and a back link.

**Data:** Expects `$stripeId`, `$cashierInvoiceCount`, `$rawInvoiceData` (array of raw Stripe invoices).

**Structure:**
*   Displays Stripe Customer ID, Cashier invoice count, raw Stripe invoice count.
*   Shows a table of raw invoice data fetched directly from Stripe.
*   Provides a list of debugging steps, including suggesting an Artisan command (`stripe:sync-invoices --all`).
*   Includes a "Return to Billing" link.

**Interaction:** Static display of diagnostic information and debugging steps.

*(Further view analysis pending...)*

### Payment Methods Management View (`payment-methods.blade.php`)

**File:** `resources/views/billing/payment-methods.blade.php`

**Purpose:** Allows users to view all saved payment methods, set a default, add new ones, and remove existing ones.

**Layout:** Uses `x-app-layout` with a header slot ("Payment Methods").

**Data:** Expects `$paymentMethods` (collection), `$defaultPaymentMethod`, `$intent` (Stripe SetupIntent). Uses `session`.

**Structure:**
*   Displays session messages.
*   "Add New Card" button toggles a hidden Stripe Elements form.
*   Lists existing payment methods with icon, brand, last 4, expiry.
*   Highlights the default method.
*   Provides "Make Default" button (POST form) for non-default methods.
*   Provides "Remove" button (DELETE form with JS confirm) for all methods.
*   Shows empty state message.
*   Footer links to Billing overview and Stripe Portal.

**JavaScript (`@push('scripts')`):**
*   Includes Stripe.js.
*   Initializes Stripe, mounts Card Element, handles errors.
*   Handles add card form submission using `confirmCardSetup` with SetupIntent, then submits to backend.
*   Toggles add card form visibility.

**Interaction:** Manages multiple payment methods, integrates Stripe Elements for adding cards, uses forms for setting default/removing.

*(Further view analysis pending...)*

### Checkout View (`checkout.blade.php`)

**File:** `resources/views/billing/checkout.blade.php`

**Purpose:** Provides a dedicated page for handling payments, likely for subscriptions or other purchases initiated via Cashier's `checkout`. Focuses solely on collecting payment details.

**Layout:** Uses `x-app-layout` with a header slot ("Checkout").

**Data:** Expects `$intent` (Stripe SetupIntent) and potentially `$priceId`. Uses `session`.

**Structure:**
*   Displays session messages.
*   Contains a single form POSTing to `billing.payment.process`.
*   Includes hidden `price_id` (if provided).
*   Includes Stripe Card Element (`#card-element`) and error display (`#card-errors`).
*   Submit button holds SetupIntent secret.
*   Provides a "Back to Billing" link.

**JavaScript (`@push('scripts')`):**
*   Includes Stripe.js.
*   Initializes Stripe, mounts Card Element, handles errors.
*   Handles form submission using `confirmCardSetup` with SetupIntent, then submits payment method ID and price ID to backend.

**Interaction:** Streamlined checkout page using Stripe Elements and SetupIntents to capture card details for payment processing.

*(Further view analysis pending...)*

### Detailed Invoice View (`invoice-details.blade.php` - Potentially Redundant)

**File:** `resources/views/billing/invoice-details.blade.php`

**Note:** This is a **full page view**, not the component `components/invoice-details.blade.php`. It appears redundant and potentially unused, as `invoice-show.blade.php` uses the component.

**Purpose:** Displays detailed invoice information, including line items, summary, payment method, and billing address.

**Layout:** Uses `x-app-layout` with header including back/download links.

**Data:** Expects `$invoice` (Cashier Invoice object). Uses `session`.

**Structure:**
*   Displays session messages.
*   Shows Invoice Header (Number, Date, Status).
*   Shows Summary (Subtotal, Tax, Discount, Total).
*   Shows Line Items table.
*   **Contains logic within the view** to fetch related Stripe PaymentMethod (for paid invoices) and Customer objects using the Stripe SDK.
*   Displays Payment Method details (Card Brand, Last 4, Expiry).
*   Displays Billing Details (Name, Email, Address).

**Interaction:** Static display of detailed invoice info. Includes logic within the view to fetch related Stripe data.

**Potential Issues:**
*   Likely redundant due to `invoice-show.blade.php` using the `x-invoice-details` component.
*   Contains business logic (Stripe SDK calls) within the view, violating separation of concerns.

*(Further view analysis pending...)*

## Email Views (`resources/views/emails/`)

### Email Test Form View (`test-form.blade.php`)

**File:** `resources/views/emails/test-form.blade.php`

**Purpose:** Provides a simple form for sending a test email (likely corresponds to `EmailController::showTestForm`).

**Layout:** Uses `x-app-layout` with a "Test Email" header.

**Data:** Uses `session` and `@error` directive.

**Structure:**
*   Displays session success/error messages.
*   Simple form POSTing to `email.test.send`.
*   Includes `@csrf`.
*   Input field for recipient email (`name="email"`) with validation error display.
*   "Send Test Email" submit button.

**Interaction:** User enters email and submits form to trigger sending of a predefined test email via the backend.

*(Further view analysis pending...)*

### Test Email Template (`test.blade.php`)

**File:** `resources/views/emails/test.blade.php`

**Purpose:** Defines the HTML structure and content for the test email sent via the `TestMail` Mailable.

**Layout:** Standalone HTML file with basic inline CSS. Does not use standard Laravel email components/markdown.

**Data:** Uses `date('Y')`.

**Structure:**
*   Simple centered container.
*   Static heading and text confirming email configuration (mentioning SES) is working.
*   Basic footer with copyright.

**Interaction:** Static email content.

*(Further view analysis pending...)*

### Jetstream Team Invitation Email (`team-invitation.blade.php`)

**File:** `resources/views/emails/team-invitation.blade.php`

**Purpose:** Standard Jetstream email template sent when a user is invited to a team.

**Layout:** Uses Laravel Markdown Mail components (`mail::message`, `mail::button`).

**Data:** Expects `$invitation` (Jetstream object) and `$acceptUrl`. Uses Fortify features check.

**Structure:**
*   States which team the user is invited to.
*   Conditionally includes a "Create Account" button if registration is enabled.
*   Includes an "Accept Invitation" button linking to `$acceptUrl`.
*   Includes discard instructions.

**Interaction:** Provides buttons to create account/accept invitation.

*(Further view analysis pending...)*

### Payment Receipt Email (`payment/receipt.blade.php`)

**File:** `resources/views/emails/payment/receipt.blade.php`

**Purpose:** Email template sent to both project owner and pitch creator upon successful payment or project completion (for free projects).

**Layout:** Standalone HTML file with basic inline CSS.

**Data:** Expects `$isFreeProject`, `$recipientName`, `$recipientType`, `$project`, `$pitch`, `$paymentDate`, `$invoiceId`, `$amount`.

**Structure:**
*   Mixpitch logo and title.
*   Greeting and text confirming payment/completion, tailored to recipient.
*   Summary box: Project Name, Pitch ID, Date, Invoice ID (if applicable), Amount/"Free Project".
*   Concluding message tailored to recipient.
*   Button linking to the web receipt view (`projects.pitches.payment.receipt`).
*   Standard footer.

**Interaction:** Confirmation email with link to web receipt.

*(Further view analysis pending...)*

## Filament Views (`resources/views/filament/`)

**Purpose:** Contains Blade views used by the Filament Admin Panel.

**Structure:** Organized into subdirectories like `pages`, `resources`, `widgets`, etc., mirroring Filament concepts.

**Analysis:** Views generally follow standard Filament patterns:
*   Use Filament layout components (e.g., `<x-filament::page>`).
*   Render widgets using `<x-filament-widgets::widgets />` based on configurations in PHP classes.
*   Embed Livewire components for specific functionality (e.g., `<livewire:email-test-form />` in `test-email-page.blade.php`).
*   Minimal custom Blade logic; complexity resides in corresponding `app/Filament/` PHP classes and embedded Livewire components.

**(Skipping detailed analysis of individual files as they follow standard Filament structure).**

*(Further view analysis pending...)*

## Vendor Views (`resources/views/vendor/`)

This directory is **empty**. No third-party package views have been published for customization.

*(Further view analysis pending...)*

## User Profile Views (`resources/views/user-profile/` & `resources/views/profile/`)

### Public Profile View (`user-profile/show.blade.php`)

**File:** `resources/views/user-profile/show.blade.php`

**Purpose:** Displays the public profile page for a given user.

**Layout:** Uses `x-app-layout`.

**Data:** Expects `$user`, `$canEdit` (boolean), `$projects` (collection), `$completedPitches` (collection).

**Structure:**
*   **Profile Header:** Avatar, Name, Username, Headline, Location, Website. Conditional "Edit Profile" button. Social media links, Tipjar link.
*   **Main Content Grid (2 columns):**
    *   **Left:** About (Bio, conditional Tipjar button), Skills/Equipment/Specialties (displayed as badges).
    *   **Right:** User's public Projects grid, Completed Pitches list.

**Interaction:** Displays profile info, projects, completed work. Includes external links (social, website, tipjar) and edit link for owner.

*(Further view analysis pending...)*

### Profile Edit View (Livewire) (`user-profile/edit-livewire.blade.php`)

**File:** `resources/views/user-profile/edit-livewire.blade.php`

**Purpose:** Container view that loads the `UserProfileEdit` Livewire component for editing the authenticated user's profile.

**Layout:** Uses `x-app-layout`.

**Data:** None passed directly; Livewire component fetches authenticated user.

**Structure:** Includes `<livewire:user-profile-edit />`.

**Interaction:** All logic handled by the embedded `UserProfileEdit` Livewire component.

*(Further view analysis pending...)*

### Profile Edit View (Standard Form - Potentially Redundant) (`user-profile/edit.blade.php`)

**File:** `resources/views/user-profile/edit.blade.php`

**Note:** This appears to be a standard HTML form implementation for profile editing, likely redundant due to the Livewire version (`user-profile/edit-livewire.blade.php` loading `UserProfileEdit`).

**Purpose:** Allows authenticated user to edit profile details via a standard form.

**Layout:** Uses `x-app-layout` with "Edit Profile" header and conditional public profile link.

**Data:** Expects `$user`. Uses `session`, `@error`, `old()`.

**Structure:**
*   Displays session messages and username setup prompt.
*   Standard HTML form POSTing to `profile.update` (PUT method).
*   Inputs for Username, Bio, Website, Location.
*   Inputs for Social Links (Twitter, Instagram, SoundCloud, Spotify, YouTube) using array format (`social_links[platform]`). Attempts to use `$this->getSocialUsername()` for pre-filling, suggesting it might have been intended for a Livewire component or requires a helper method.
*   "Save" button.

**Interaction:** Standard HTML form submission to `UserProfileController::update`.

**Redundancy:** Likely redundant/unused in favor of the Livewire implementation.

*(Further view analysis pending...)*

### Jetstream Profile Views (`resources/views/profile/`)

**Purpose:** Contains standard Jetstream views for user profile management (update info, password, 2FA, browser sessions, delete account).

**Structure:** The main view (`show.blade.php`) uses `x-app-layout` and conditionally includes various `@livewire('profile.*')` components based on enabled Jetstream/Fortify features. Other files in the directory are the Blade views for these embedded Livewire components.

**Interaction:** All logic is handled by the embedded Jetstream Livewire components.

**(Skipping detailed analysis of individual files as they are standard Jetstream components).**

*(Further view analysis pending...)*

## Pitch Files Views (`resources/views/pitch-files/`)

### Pitch File Show View (`show.blade.php`)

**File:** `resources/views/pitch-files/show.blade.php`

**Purpose:** Container view to display a single pitch file using the `PitchFilePlayer` Livewire component.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$file` (PitchFile model).

**Structure:**
*   Includes `<livewire:pitch-file-player :file="$file" />`.
*   Pushes basic CSS for waveform loading/timeline.
*   Pushes Wavesurfer.js library.

**Interaction:** All logic handled by the embedded `PitchFilePlayer` Livewire component.

*(Further view analysis pending...)*

## Authentication Views (`resources/views/auth/`)

**Purpose:** Contains standard Laravel Fortify views for handling authentication flows (login, registration, password reset, two-factor auth, etc.).

**Structure:** Views use `x-guest-layout` and standard Jetstream/Fortify Blade components (e.g., `x-authentication-card`). They correspond to Fortify's backend routes and actions.

**Interaction:** Standard HTML forms submitting to Fortify controllers/actions.

**(Skipping detailed analysis of individual files as they are standard Fortify views).**

*(Further view analysis pending...)*

## API Token Views (`resources/views/api/`)

**Purpose:** Contains standard Jetstream views for API token management.

**Structure:** The main view (`index.blade.php`) uses `x-app-layout` and includes the `@livewire('api.api-token-manager')` component. The other file (`api-token-manager.blade.php`) is the view for that Livewire component.

**Interaction:** All logic handled by the embedded Jetstream Livewire component.

**(Skipping detailed analysis as they are standard Jetstream components).**

*(Further view analysis pending...)*

## Projects Views (`resources/views/projects/`)

### Public Project Show View (`project.blade.php`)

**File:** `resources/views/projects/project.blade.php`

**Purpose:** Displays the public detail page for a single project.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project` (with relations), `$canPitch` (boolean), `$userPitch` (Pitch model or null). Uses `Auth`.

**Structure:**
*   **Header Card:** Project image (Alpine lightbox), preview track audio player (Livewire), Project Name, Artist, Owner (`x-user-link`), Status (`x-project-status-button`), Type, Deadline. Conditional buttons (Manage Project / Start Pitch / View Pitch).
*   **Details Section:** Collaboration badges, Budget, Description, Notes.

**Interaction:** Displays project details. Conditional actions for owner (Manage) or others (Start/View Pitch). Pitch initiation uses JS modal (`openPitchTermsModal()`). Includes audio player and image lightbox.

*(Further view analysis pending...)*

### Project Edit View (Standard Form - Potentially Redundant) (`edit.blade.php`)

**File:** `resources/views/projects/edit.blade.php`

**Note:** Likely redundant due to the `ManageProject` Livewire component handling project editing and file management.

**Purpose:** Allows editing project details and managing files via standard HTML forms.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project` (with relations). Uses `old()`.

**Structure:**
*   **Header:** Displays project image (clickable for upload via JS), inputs for Name, Description, Genre, Status.
*   **Form:** Standard HTML form POSTing to `projects.update` (PUT method), `enctype="multipart/form-data"`.
*   **File Management:** Lists existing files with Delete buttons (individual DELETE forms triggered by JS). Links to a separate `projects.createStep2` route for uploads.

**JavaScript:** Includes `loadFile` for image preview and event listener for delete buttons.

**Interaction:** Standard form submission for edits. File deletion via separate forms triggered by JS. Links to potentially deprecated upload step.

**Redundancy:** Likely redundant/unused in favor of the `ManageProject` Livewire implementation.

### Project Index View (`index.blade.php` - Livewire Wrapper)

**File:** `resources/views/projects/index.blade.php`

**Purpose:** Container view for the public project listing.

**Layout:** Extends `components.layouts.app`.

**Structure:** Includes `<livewire:projects-component />`. Assumes the listing logic is handled by this Livewire component.

### Project Upload (Step 2) View (`create_step2.blade.php` - Likely Deprecated)

**File:** `resources/views/projects/create_step2.blade.php`

**Purpose:** Provides a Dropzone.js interface for uploading files as part of a multi-step project creation/edit process.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project`.

**Structure:** Includes Dropzone CSS/JS. Contains Dropzone form POSTing to `projects.storeStep2`. Includes Back/Finish links.

**JavaScript:** Initializes Dropzone.

**Redundancy:** Uses Dropzone and separate upload step, contrasting with integrated S3 uploads in `ManageProject`. Highly likely deprecated.

### Upload Project View (`upload-project.blade.php` - TrackController related)

**File:** `resources/views/projects/upload-project.blade.php`

**Purpose:** Container view, likely related to the `TrackController` functionality.

**Layout:** Extends `components.layouts.app`.

**Structure:** Includes `<livewire:upload-project-component />`. Logic is assumed to be in this Livewire component.

*(Further view analysis pending...)*

## Components (`resources/views/components/`)

### Invoice Details Component (`invoice-details.blade.php`)

**File:** `resources/views/components/invoice-details.blade.php`

**Purpose:** Renders a detailed view of a single invoice, likely using data fetched from Stripe.

**Usage:** Expected to be used like `<x-invoice-details :invoice="$invoiceObject" :view-all-url="route(...)" />`

**Input Props:**
*   `$invoice`: An object containing invoice data (ID/number, status, date, total, description, line items). Structure suggests compatibility with Stripe API responses or formatted objects from `InvoiceService`.
*   `$viewAllUrl` (optional): A URL string for a 'View all invoices' link.

**Key Features:**
*   Displays invoice number, paid/unpaid status badge, date, and total amount.
*   Lists line items from `$invoice->lines->data` or shows a single summary line.
*   Provides a 'Download PDF' link to `route('billing.invoice.download', $invoice->id)`.
*   Optionally provides a 'View all invoices' link.
*   Styled with Tailwind CSS.

**Interaction:** Primarily a display component formatting invoice data. Provides navigation links for downloading and viewing other invoices.

*(Further view analysis pending...)*

### Update Pitch Status Buttons Component (`update-pitch-status.blade.php`)

**File:** `resources/views/components/update-pitch-status.blade.php`

**Purpose:** Renders action buttons for changing a pitch's status based on its current state.
**Note: This component appears highly problematic and likely redundant/deprecated.** Its logic seems to belong within the `UpdatePitchStatus` Livewire component's view (`resources/views/livewire/pitch/component/update-pitch-status.blade.php`). This Blade component bypasses the Livewire component's actions and service layer calls in many cases.

**Usage:** Likely intended usage `<x-update-pitch-status :pitch="$pitch" />`

**Input Props:**
*   `$pitch`: The `Pitch` model.

**Key Features & Issues:**
*   **Conditional Logic:** Uses extensive `@if/@elseif` based on `$pitch->status` to determine which actions/buttons to show.
*   **Direct Form Submissions (Issue):** For several statuses (Pending, In Progress, Approved, Denied, Revisions Requested, Completed), it renders HTML forms that POST directly to controller routes (`projects.pitches.change-status`, `projects.pitches.return-to-approved`). This bypasses the `UpdatePitchStatus` Livewire component and the `PitchWorkflowService`, potentially leading to inconsistent state management and authorization issues.
*   **JavaScript Modal Triggers (Partial Alignment):** For the `READY_FOR_REVIEW` status, it renders buttons that trigger JavaScript functions (`openApproveModal`, etc.) presumably defined elsewhere to interact with modals (`<x-pitch-action-modals />`). This aligns partially with the Livewire component's modal interaction but still passes controller routes directly to JS instead of using Livewire actions.
*   **Includes Modals:** Includes `<x-pitch-action-modals />`.

**Interaction:** Renders conditional buttons/forms. Interacts via direct form POSTs or JavaScript calls, largely bypassing the corresponding Livewire component. Includes a modal component.

**Recommendation:** This component should be reviewed and likely removed. Its logic needs to be consolidated within the `UpdatePitchStatus` Livewire component and its associated view to ensure consistent use of the `PitchWorkflowService` and proper state management.

*(Further view analysis pending...)*

### Pitch Action Modals Component (`pitch-action-modals.blade.php`)

**File:** `resources/views/components/pitch-action-modals.blade.php`

**Purpose:** Defines the HTML structure for modals used for Approve, Deny, and Request Revisions actions on pitches.

**Usage:** Included by other components, e.g., `<x-pitch-action-modals />` (as seen in `update-pitch-status.blade.php`).

**Input Props:** None.

**Key Features:**
*   **Structure:** Contains three distinct modal sections (`#approveModal`, `#denyModal`, `#revisionsModal`), initially hidden.
*   **Forms:** Each modal contains a basic, hidden HTML form (`#approveForm`, etc.) with only `@csrf`. JavaScript is expected to set the `action` URL and potentially add data before submission.
*   **Inputs:** Provides textareas for denial reason (`#denyReason`) and revision requests (`#revisionsRequested`).
*   **Buttons:** Includes Cancel buttons (`onclick="closeModal(...)"`) and confirmation buttons (`#approveSubmitBtn`, etc.) presumably handled by JavaScript.
*   **JavaScript Dependency:** Includes `<script src="{{ asset('js/pitch-modals.js') }}"></script>`, indicating all modal interaction logic (opening, closing, submitting the hidden forms) resides in this external JS file.
*   **Styling:** Uses Tailwind CSS.

**Interaction:** Provides the static modal HTML. Relies entirely on `pitch-modals.js` (and functions like `openApproveModal`, `closeModal`) for display logic and form submission, targeting controller routes directly. This approach is less integrated with Livewire/Alpine compared to using Livewire components for modal state and actions.

**Note:** The reliance on external JS to manipulate and submit hidden forms directly to controller routes is part of the potentially problematic pattern identified in `components/update-pitch-status.blade.php`.

*(Further view analysis pending...)*

### Pitch Terms Modal Component (`pitch-terms-modal.blade.php`)

**File:** `resources/views/components/pitch-terms-modal.blade.php`

**Purpose:** Displays a modal with terms highlights that must be agreed to before initiating a pitch creation request.

**Usage:** Likely included on project detail pages, e.g., `<x-pitch-terms-modal :project="$project" />`. Triggered via JavaScript (`openPitchTermsModal()`).

**Input Props:**
*   `$project`: The `Project` model the user wants to pitch for.

**Key Features:**
*   **Structure:** Defines a modal (`#pitch-terms-modal`) with header, body (terms highlights, full terms link, agreement checkbox), and footer.
*   **Form:** Contains a hidden HTML form (`#pitch-create-form`) targeting `route('projects.pitches.store')` via POST. Includes `@csrf` and `$project->id`.
*   **Agreement:** Requires user to check an 'Agree to Terms' checkbox (`#agree_terms`).
*   **JavaScript:** Includes inline JS defining global functions (`openPitchTermsModal`, `closePitchTermsModal`, `submitPitchForm`) for modal control and form submission. `submitPitchForm` validates checkbox before submitting.
*   **Styling:** Uses Tailwind CSS.

**Interaction:** Provides the UI flow for agreeing to terms before creating a pitch. Uses inline JavaScript for modal display and conditional form submission. Submits directly to a controller route upon confirmation.

*(Further view analysis pending...)*

### User Link Component (`user-link.blade.php`)

**File:** `resources/views/components/user-link.blade.php`

**Purpose:** Renders a user's name, providing a link to their public profile if they have a username.

**Usage:** `<x-user-link :user="$userObject" />`

**Input Props:**
*   `$user`: The `User` model instance.

**Key Features:**
*   Conditionally renders an anchor tag linking to `route('profile.username', ['username' => $user->username])` if `$user->username` is set.
*   Displays the `$user->name` as the link text or plain text.
*   Basic Tailwind link styling.

**Interaction:** Displays user name, conditionally linking to their profile.

*(Further view analysis pending...)*

### Pitch Status Banner Component (`pitch-status.blade.php`)

**File:** `resources/views/components/pitch-status.blade.php`

**Purpose:** Renders a prominent banner displaying a formatted pitch status with dynamic background and text colors.

**Usage:** Likely used via `@include('components.pitch-status', ['status' => $statusString, 'bgColor' => $tailwindBgClass, 'textColor' => $tailwindTextClass])`

**Input Props (Implicit):**
*   `$status`: The pitch status string (e.g., 'ready_for_review').
*   `$bgColor`: A Tailwind background color class (e.g., 'bg-blue-100').
*   `$textColor`: A Tailwind text color class (e.g., 'text-blue-800').

**Key Features:**
*   Formats the `$status` string using `ucwords(str_replace('_', ' ', $status))` for display.
*   Dynamically applies background and text color classes passed via `$bgColor` and `$textColor` using `@apply`.
*   Styled with Tailwind CSS.

**Interaction:** Purely a display component for showing status in a styled banner.

*(Further view analysis pending...)*

### Project Status Button Component (`project-status-button.blade.php`)

**File:** `resources/views/components/project-status-button.blade.php`

**Purpose:** Renders a styled badge or indicator for a project's status, including a relevant icon.

**Usage:** Likely used via `@include('components.project-status-button', ['status' => $project->status, 'type' => 'inline'])` or within Blade component context.

**Input Props (Implicit):**
*   `$status`: The project status string ('unpublished', 'open', 'review', 'completed', 'closed').
*   `$type`: Controls styling variations (e.g., 'top-right' for specific positioning/rounding, defaults to 'inline' styling).

**Key Features:**
*   **Status Mapping:** Uses a PHP `@switch` statement to map project `$status` strings to specific Tailwind CSS classes (`$colorClass`) and Font Awesome icon classes (`$iconClass`).
*   **Custom Colors:** Uses custom color names (e.g., `bg-statusOpen`, `shadow-statusOpen`) presumably defined in Tailwind config or CSS.
*   **Conditional Styling:** Applies different `border-radius` styles based on the `$type` variable.
*   **Display:** Renders a `<span>` containing the icon and the raw `$status` string.
*   **Styling:** Uses Tailwind CSS and Font Awesome.

**Interaction:** Purely a display component for project status indication.

*(Further view analysis pending...)*

### Invoice List View (`invoices.blade.php`)

**File:** `resources/views/billing/invoices.blade.php`

**Purpose:** Displays a paginated list of all the user's invoices.

**Layout:** Uses `x-app-layout` with a header slot ("Billing History") and a back link.

**Data:** Expects an `$invoices` collection (likely paginated Cashier Invoice objects). Uses `session`.

**Structure:**
*   Displays session messages.
*   Shows an empty state if no invoices exist.
*   Displays invoices in a table: Number, Date, Amount, Status (Paid/Unpaid), Type (Pitch Payment/Standard), Actions (View/Download).
*   "Type" column checks metadata for `pitch_id` and links to the project if applicable.
*   Provides "View" and "Download" links per invoice.

**Interaction:** Displays invoice list. Provides links to view/download invoices and navigate to related projects. Assumes pagination if data is paginated.

*(Further view analysis pending...)*

### Billing Diagnostic View (`diagnostic.blade.php`)

**File:** `resources/views/billing/diagnostic.blade.php`

**Purpose:** Developer/admin tool to diagnose invoice synchronization issues by comparing raw Stripe invoice data with Cashier's local count.

**Layout:** Uses `x-app-layout` with a header slot ("Invoice Diagnostic") and a back link.

**Data:** Expects `$stripeId`, `$cashierInvoiceCount`, `$rawInvoiceData` (array of raw Stripe invoices).

**Structure:**
*   Displays Stripe Customer ID, Cashier invoice count, raw Stripe invoice count.
*   Shows a table of raw invoice data fetched directly from Stripe.
*   Provides a list of debugging steps, including suggesting an Artisan command (`stripe:sync-invoices --all`).
*   Includes a "Return to Billing" link.

**Interaction:** Static display of diagnostic information and debugging steps.

*(Further view analysis pending...)*

### Payment Methods Management View (`payment-methods.blade.php`)

**File:** `resources/views/billing/payment-methods.blade.php`

**Purpose:** Allows users to view all saved payment methods, set a default, add new ones, and remove existing ones.

**Layout:** Uses `x-app-layout` with a header slot ("Payment Methods").

**Data:** Expects `$paymentMethods` (collection), `$defaultPaymentMethod`, `$intent` (Stripe SetupIntent). Uses `session`.

**Structure:**
*   Displays session messages.
*   "Add New Card" button toggles a hidden Stripe Elements form.
*   Lists existing payment methods with icon, brand, last 4, expiry.
*   Highlights the default method.
*   Provides "Make Default" button (POST form) for non-default methods.
*   Provides "Remove" button (DELETE form with JS confirm) for all methods.
*   Shows empty state message.
*   Footer links to Billing overview and Stripe Portal.

**JavaScript (`@push('scripts')`):**
*   Includes Stripe.js.
*   Initializes Stripe, mounts Card Element, handles errors.
*   Handles add card form submission using `confirmCardSetup` with SetupIntent, then submits to backend.
*   Toggles add card form visibility.

**Interaction:** Manages multiple payment methods, integrates Stripe Elements for adding cards, uses forms for setting default/removing.

*(Further view analysis pending...)*

### Checkout View (`checkout.blade.php`)

**File:** `resources/views/billing/checkout.blade.php`

**Purpose:** Provides a dedicated page for handling payments, likely for subscriptions or other purchases initiated via Cashier's `checkout`. Focuses solely on collecting payment details.

**Layout:** Uses `x-app-layout` with a header slot ("Checkout").

**Data:** Expects `$intent` (Stripe SetupIntent) and potentially `$priceId`. Uses `session`.

**Structure:**
*   Displays session messages.
*   Contains a single form POSTing to `billing.payment.process`.
*   Includes hidden `price_id` (if provided).
*   Includes Stripe Card Element (`#card-element`) and error display (`#card-errors`).
*   Submit button holds SetupIntent secret.
*   Provides a "Back to Billing" link.

**JavaScript (`@push('scripts')`):**
*   Includes Stripe.js.
*   Initializes Stripe, mounts Card Element, handles errors.
*   Handles form submission using `confirmCardSetup` with SetupIntent, then submits payment method ID and price ID to backend.

**Interaction:** Streamlined checkout page using Stripe Elements and SetupIntents to capture card details for payment processing.

*(Further view analysis pending...)*

### Detailed Invoice View (`invoice-details.blade.php` - Potentially Redundant)

**File:** `resources/views/billing/invoice-details.blade.php`

**Note:** This is a **full page view**, not the component `components/invoice-details.blade.php`. It appears redundant and potentially unused, as `invoice-show.blade.php` uses the component.

**Purpose:** Displays detailed invoice information, including line items, summary, payment method, and billing address.

**Layout:** Uses `x-app-layout` with header including back/download links.

**Data:** Expects `$invoice` (Cashier Invoice object). Uses `session`.

**Structure:**
*   Displays session messages.
*   Shows Invoice Header (Number, Date, Status).
*   Shows Summary (Subtotal, Tax, Discount, Total).
*   Shows Line Items table.
*   **Contains logic within the view** to fetch related Stripe PaymentMethod (for paid invoices) and Customer objects using the Stripe SDK.
*   Displays Payment Method details (Card Brand, Last 4, Expiry).
*   Displays Billing Details (Name, Email, Address).

**Interaction:** Static display of detailed invoice info. Includes logic within the view to fetch related Stripe data.

**Potential Issues:**
*   Likely redundant due to `invoice-show.blade.php` using the `x-invoice-details` component.
*   Contains business logic (Stripe SDK calls) within the view, violating separation of concerns.

*(Further view analysis pending...)*

## Email Views (`resources/views/emails/`)

### Email Test Form View (`test-form.blade.php`)

**File:** `resources/views/emails/test-form.blade.php`

**Purpose:** Provides a simple form for sending a test email (likely corresponds to `EmailController::showTestForm`).

**Layout:** Uses `x-app-layout` with a "Test Email" header.

**Data:** Uses `session` and `@error` directive.

**Structure:**
*   Displays session success/error messages.
*   Simple form POSTing to `email.test.send`.
*   Includes `@csrf`.
*   Input field for recipient email (`name="email"`) with validation error display.
*   "Send Test Email" submit button.

**Interaction:** User enters email and submits form to trigger sending of a predefined test email via the backend.

*(Further view analysis pending...)*

### Test Email Template (`test.blade.php`)

**File:** `resources/views/emails/test.blade.php`

**Purpose:** Defines the HTML structure and content for the test email sent via the `TestMail` Mailable.

**Layout:** Standalone HTML file with basic inline CSS. Does not use standard Laravel email components/markdown.

**Data:** Uses `date('Y')`.

**Structure:**
*   Simple centered container.
*   Static heading and text confirming email configuration (mentioning SES) is working.
*   Basic footer with copyright.

**Interaction:** Static email content.

*(Further view analysis pending...)*

### Jetstream Team Invitation Email (`team-invitation.blade.php`)

**File:** `resources/views/emails/team-invitation.blade.php`

**Purpose:** Standard Jetstream email template sent when a user is invited to a team.

**Layout:** Uses Laravel Markdown Mail components (`mail::message`, `mail::button`).

**Data:** Expects `$invitation` (Jetstream object) and `$acceptUrl`. Uses Fortify features check.

**Structure:**
*   States which team the user is invited to.
*   Conditionally includes a "Create Account" button if registration is enabled.
*   Includes an "Accept Invitation" button linking to `$acceptUrl`.
*   Includes discard instructions.

**Interaction:** Provides buttons to create account/accept invitation.

*(Further view analysis pending...)*

### Payment Receipt Email (`payment/receipt.blade.php`)

**File:** `resources/views/emails/payment/receipt.blade.php`

**Purpose:** Email template sent to both project owner and pitch creator upon successful payment or project completion (for free projects).

**Layout:** Standalone HTML file with basic inline CSS.

**Data:** Expects `$isFreeProject`, `$recipientName`, `$recipientType`, `$project`, `$pitch`, `$paymentDate`, `$invoiceId`, `$amount`.

**Structure:**
*   Mixpitch logo and title.
*   Greeting and text confirming payment/completion, tailored to recipient.
*   Summary box: Project Name, Pitch ID, Date, Invoice ID (if applicable), Amount/"Free Project".
*   Concluding message tailored to recipient.
*   Button linking to the web receipt view (`projects.pitches.payment.receipt`).
*   Standard footer.

**Interaction:** Confirmation email with link to web receipt.

*(Further view analysis pending...)*

## Filament Views (`resources/views/filament/`)

**Purpose:** Contains Blade views used by the Filament Admin Panel.

**Structure:** Organized into subdirectories like `pages`, `resources`, `widgets`, etc., mirroring Filament concepts.

**Analysis:** Views generally follow standard Filament patterns:
*   Use Filament layout components (e.g., `<x-filament::page>`).
*   Render widgets using `<x-filament-widgets::widgets />` based on configurations in PHP classes.
*   Embed Livewire components for specific functionality (e.g., `<livewire:email-test-form />` in `test-email-page.blade.php`).
*   Minimal custom Blade logic; complexity resides in corresponding `app/Filament/` PHP classes and embedded Livewire components.

**(Skipping detailed analysis of individual files as they follow standard Filament structure).**

*(Further view analysis pending...)*

## Vendor Views (`resources/views/vendor/`)

This directory is **empty**. No third-party package views have been published for customization.

*(Further view analysis pending...)*

## User Profile Views (`resources/views/user-profile/` & `resources/views/profile/`)

### Public Profile View (`user-profile/show.blade.php`)

**File:** `resources/views/user-profile/show.blade.php`

**Purpose:** Displays the public profile page for a given user.

**Layout:** Uses `x-app-layout`.

**Data:** Expects `$user`, `$canEdit` (boolean), `$projects` (collection), `$completedPitches` (collection).

**Structure:**
*   **Profile Header:** Avatar, Name, Username, Headline, Location, Website. Conditional "Edit Profile" button. Social media links, Tipjar link.
*   **Main Content Grid (2 columns):**
    *   **Left:** About (Bio, conditional Tipjar button), Skills/Equipment/Specialties (displayed as badges).
    *   **Right:** User's public Projects grid, Completed Pitches list.

**Interaction:** Displays profile info, projects, completed work. Includes external links (social, website, tipjar) and edit link for owner.

*(Further view analysis pending...)*

### Profile Edit View (Livewire) (`user-profile/edit-livewire.blade.php`)

**File:** `resources/views/user-profile/edit-livewire.blade.php`

**Purpose:** Container view that loads the `UserProfileEdit` Livewire component for editing the authenticated user's profile.

**Layout:** Uses `x-app-layout`.

**Data:** None passed directly; Livewire component fetches authenticated user.

**Structure:** Includes `<livewire:user-profile-edit />`.

**Interaction:** All logic handled by the embedded `UserProfileEdit` Livewire component.

*(Further view analysis pending...)*

### Profile Edit View (Standard Form - Potentially Redundant) (`user-profile/edit.blade.php`)

**File:** `resources/views/user-profile/edit.blade.php`

**Note:** This appears to be a standard HTML form implementation for profile editing, likely redundant due to the Livewire version (`user-profile/edit-livewire.blade.php` loading `UserProfileEdit`).

**Purpose:** Allows authenticated user to edit profile details via a standard form.

**Layout:** Uses `x-app-layout` with "Edit Profile" header and conditional public profile link.

**Data:** Expects `$user`. Uses `session`, `@error`, `old()`.

**Structure:**
*   Displays session messages and username setup prompt.
*   Standard HTML form POSTing to `profile.update` (PUT method).
*   Inputs for Username, Bio, Website, Location.
*   Inputs for Social Links (Twitter, Instagram, SoundCloud, Spotify, YouTube) using array format (`social_links[platform]`). Attempts to use `$this->getSocialUsername()` for pre-filling, suggesting it might have been intended for a Livewire component or requires a helper method.
*   "Save" button.

**Interaction:** Standard HTML form submission to `UserProfileController::update`.

**Redundancy:** Likely redundant/unused in favor of the Livewire implementation.

*(Further view analysis pending...)*

### Jetstream Profile Views (`resources/views/profile/`)

**Purpose:** Contains standard Jetstream views for user profile management (update info, password, 2FA, browser sessions, delete account).

**Structure:** The main view (`show.blade.php`) uses `x-app-layout` and conditionally includes various `@livewire('profile.*')` components based on enabled Jetstream/Fortify features. Other files in the directory are the Blade views for these embedded Livewire components.

**Interaction:** All logic is handled by the embedded Jetstream Livewire components.

**(Skipping detailed analysis of individual files as they are standard Jetstream components).**

*(Further view analysis pending...)*

## Pitch Files Views (`resources/views/pitch-files/`)

### Pitch File Show View (`show.blade.php`)

**File:** `resources/views/pitch-files/show.blade.php`

**Purpose:** Container view to display a single pitch file using the `PitchFilePlayer` Livewire component.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$file` (PitchFile model).

**Structure:**
*   Includes `<livewire:pitch-file-player :file="$file" />`.
*   Pushes basic CSS for waveform loading/timeline.
*   Pushes Wavesurfer.js library.

**Interaction:** All logic handled by the embedded `PitchFilePlayer` Livewire component.

*(Further view analysis pending...)*

## Authentication Views (`resources/views/auth/`)

**Purpose:** Contains standard Laravel Fortify views for handling authentication flows (login, registration, password reset, two-factor auth, etc.).

**Structure:** Views use `x-guest-layout` and standard Jetstream/Fortify Blade components (e.g., `x-authentication-card`). They correspond to Fortify's backend routes and actions.

**Interaction:** Standard HTML forms submitting to Fortify controllers/actions.

**(Skipping detailed analysis of individual files as they are standard Fortify views).**

*(Further view analysis pending...)*

## API Token Views (`resources/views/api/`)

**Purpose:** Contains standard Jetstream views for API token management.

**Structure:** The main view (`index.blade.php`) uses `x-app-layout` and includes the `@livewire('api.api-token-manager')` component. The other file (`api-token-manager.blade.php`) is the view for that Livewire component.

**Interaction:** All logic handled by the embedded Jetstream Livewire component.

**(Skipping detailed analysis as they are standard Jetstream components).**

*(Further view analysis pending...)*

## Projects Views (`resources/views/projects/`)

### Public Project Show View (`project.blade.php`)

**File:** `resources/views/projects/project.blade.php`

**Purpose:** Displays the public detail page for a single project.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project` (with relations), `$canPitch` (boolean), `$userPitch` (Pitch model or null). Uses `Auth`.

**Structure:**
*   **Header Card:** Project image (Alpine lightbox), preview track audio player (Livewire), Project Name, Artist, Owner (`x-user-link`), Status (`x-project-status-button`), Type, Deadline. Conditional buttons (Manage Project / Start Pitch / View Pitch).
*   **Details Section:** Collaboration badges, Budget, Description, Notes.

**Interaction:** Displays project details. Conditional actions for owner (Manage) or others (Start/View Pitch). Pitch initiation uses JS modal (`openPitchTermsModal()`). Includes audio player and image lightbox.

*(Further view analysis pending...)*

### Project Edit View (Standard Form - Potentially Redundant) (`edit.blade.php`)

**File:** `resources/views/projects/edit.blade.php`

**Note:** Likely redundant due to the `ManageProject` Livewire component handling project editing and file management.

**Purpose:** Allows editing project details and managing files via standard HTML forms.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project` (with relations). Uses `old()`.

**Structure:**
*   **Header:** Displays project image (clickable for upload via JS), inputs for Name, Description, Genre, Status.
*   **Form:** Standard HTML form POSTing to `projects.update` (PUT method), `enctype="multipart/form-data"`.
*   **File Management:** Lists existing files with Delete buttons (individual DELETE forms triggered by JS). Links to a separate `projects.createStep2` route for uploads.

**JavaScript:** Includes `loadFile` for image preview and event listener for delete buttons.

**Interaction:** Standard form submission for edits. File deletion via separate forms triggered by JS. Links to potentially deprecated upload step.

**Redundancy:** Likely redundant/unused in favor of the `ManageProject` Livewire implementation.

### Project Index View (`index.blade.php` - Livewire Wrapper)

**File:** `resources/views/projects/index.blade.php`

**Purpose:** Container view for the public project listing.

**Layout:** Extends `components.layouts.app`.

**Structure:** Includes `<livewire:projects-component />`. Assumes the listing logic is handled by this Livewire component.

### Project Upload (Step 2) View (`create_step2.blade.php` - Likely Deprecated)

**File:** `resources/views/projects/create_step2.blade.php`

**Purpose:** Provides a Dropzone.js interface for uploading files as part of a multi-step project creation/edit process.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project`.

**Structure:** Includes Dropzone CSS/JS. Contains Dropzone form POSTing to `projects.storeStep2`. Includes Back/Finish links.

**JavaScript:** Initializes Dropzone.

**Redundancy:** Uses Dropzone and separate upload step, contrasting with integrated S3 uploads in `ManageProject`. Highly likely deprecated.

### Upload Project View (`upload-project.blade.php` - TrackController related)

**File:** `resources/views/projects/upload-project.blade.php`

**Purpose:** Container view, likely related to the `TrackController` functionality.

**Layout:** Extends `components.layouts.app`.

**Structure:** Includes `<livewire:upload-project-component />`. Logic is assumed to be in this Livewire component.

*(Further view analysis pending...)*

## Components (`resources/views/components/`)

### Invoice Details Component (`invoice-details.blade.php`)

**File:** `resources/views/components/invoice-details.blade.php`

**Purpose:** Renders a detailed view of a single invoice, likely using data fetched from Stripe.

**Usage:** Expected to be used like `<x-invoice-details :invoice="$invoiceObject" :view-all-url="route(...)" />`

**Input Props:**
*   `$invoice`: An object containing invoice data (ID/number, status, date, total, description, line items). Structure suggests compatibility with Stripe API responses or formatted objects from `InvoiceService`.
*   `$viewAllUrl` (optional): A URL string for a 'View all invoices' link.

**Key Features:**
*   Displays invoice number, paid/unpaid status badge, date, and total amount.
*   Lists line items from `$invoice->lines->data` or shows a single summary line.
*   Provides a 'Download PDF' link to `route('billing.invoice.download', $invoice->id)`.
*   Optionally provides a 'View all invoices' link.
*   Styled with Tailwind CSS.

**Interaction:** Primarily a display component formatting invoice data. Provides navigation links for downloading and viewing other invoices.

*(Further view analysis pending...)*

### Update Pitch Status Buttons Component (`update-pitch-status.blade.php`)

**File:** `resources/views/components/update-pitch-status.blade.php`

**Purpose:** Renders action buttons for changing a pitch's status based on its current state.
**Note: This component appears highly problematic and likely redundant/deprecated.** Its logic seems to belong within the `UpdatePitchStatus` Livewire component's view (`resources/views/livewire/pitch/component/update-pitch-status.blade.php`). This Blade component bypasses the Livewire component's actions and service layer calls in many cases.

**Usage:** Likely intended usage `<x-update-pitch-status :pitch="$pitch" />`

**Input Props:**
*   `$pitch`: The `Pitch` model.

**Key Features & Issues:**
*   **Conditional Logic:** Uses extensive `@if/@elseif` based on `$pitch->status` to determine which actions/buttons to show.
*   **Direct Form Submissions (Issue):** For several statuses (Pending, In Progress, Approved, Denied, Revisions Requested, Completed), it renders HTML forms that POST directly to controller routes (`projects.pitches.change-status`, `projects.pitches.return-to-approved`). This bypasses the `UpdatePitchStatus` Livewire component and the `PitchWorkflowService`, potentially leading to inconsistent state management and authorization issues.
*   **JavaScript Modal Triggers (Partial Alignment):** For the `READY_FOR_REVIEW` status, it renders buttons that trigger JavaScript functions (`openApproveModal`, etc.) presumably defined elsewhere to interact with modals (`<x-pitch-action-modals />`). This aligns partially with the Livewire component's modal interaction but still passes controller routes directly to JS instead of using Livewire actions.
*   **Includes Modals:** Includes `<x-pitch-action-modals />`.

**Interaction:** Renders conditional buttons/forms. Interacts via direct form POSTs or JavaScript calls, largely bypassing the corresponding Livewire component. Includes a modal component.

**Recommendation:** This component should be reviewed and likely removed. Its logic needs to be consolidated within the `UpdatePitchStatus` Livewire component and its associated view to ensure consistent use of the `PitchWorkflowService` and proper state management.

*(Further view analysis pending...)*

### Pitch Action Modals Component (`pitch-action-modals.blade.php`)

**File:** `resources/views/components/pitch-action-modals.blade.php`

**Purpose:** Defines the HTML structure for modals used for Approve, Deny, and Request Revisions actions on pitches.

**Usage:** Included by other components, e.g., `<x-pitch-action-modals />` (as seen in `update-pitch-status.blade.php`).

**Input Props:** None.

**Key Features:**
*   **Structure:** Contains three distinct modal sections (`#approveModal`, `#denyModal`, `#revisionsModal`), initially hidden.
*   **Forms:** Each modal contains a basic, hidden HTML form (`#approveForm`, etc.) with only `@csrf`. JavaScript is expected to set the `action` URL and potentially add data before submission.
*   **Inputs:** Provides textareas for denial reason (`#denyReason`) and revision requests (`#revisionsRequested`).
*   **Buttons:** Includes Cancel buttons (`onclick="closeModal(...)"`) and confirmation buttons (`#approveSubmitBtn`, etc.) presumably handled by JavaScript.
*   **JavaScript Dependency:** Includes `<script src="{{ asset('js/pitch-modals.js') }}"></script>`, indicating all modal interaction logic (opening, closing, submitting the hidden forms) resides in this external JS file.
*   **Styling:** Uses Tailwind CSS.

**Interaction:** Provides the static modal HTML. Relies entirely on `pitch-modals.js` (and functions like `openApproveModal`, `closeModal`) for display logic and form submission, targeting controller routes directly. This approach is less integrated with Livewire/Alpine compared to using Livewire components for modal state and actions.

**Note:** The reliance on external JS to manipulate and submit hidden forms directly to controller routes is part of the potentially problematic pattern identified in `components/update-pitch-status.blade.php`.

*(Further view analysis pending...)*

### Pitch Terms Modal Component (`pitch-terms-modal.blade.php`)

**File:** `resources/views/components/pitch-terms-modal.blade.php`

**Purpose:** Displays a modal with terms highlights that must be agreed to before initiating a pitch creation request.

**Usage:** Likely included on project detail pages, e.g., `<x-pitch-terms-modal :project="$project" />`. Triggered via JavaScript (`openPitchTermsModal()`).

**Input Props:**
*   `$project`: The `Project` model the user wants to pitch for.

**Key Features:**
*   **Structure:** Defines a modal (`#pitch-terms-modal`) with header, body (terms highlights, full terms link, agreement checkbox), and footer.
*   **Form:** Contains a hidden HTML form (`#pitch-create-form`) targeting `route('projects.pitches.store')` via POST. Includes `@csrf` and `$project->id`.
*   **Agreement:** Requires user to check an 'Agree to Terms' checkbox (`#agree_terms`).
*   **JavaScript:** Includes inline JS defining global functions (`openPitchTermsModal`, `closePitchTermsModal`, `submitPitchForm`) for modal control and form submission. `submitPitchForm` validates checkbox before submitting.
*   **Styling:** Uses Tailwind CSS.

**Interaction:** Provides the UI flow for agreeing to terms before creating a pitch. Uses inline JavaScript for modal display and conditional form submission. Submits directly to a controller route upon confirmation.

*(Further view analysis pending...)*

### User Link Component (`user-link.blade.php`)

**File:** `resources/views/components/user-link.blade.php`

**Purpose:** Renders a user's name, providing a link to their public profile if they have a username.

**Usage:** `<x-user-link :user="$userObject" />`

**Input Props:**
*   `$user`: The `User` model instance.

**Key Features:**
*   Conditionally renders an anchor tag linking to `route('profile.username', ['username' => $user->username])` if `$user->username` is set.
*   Displays the `$user->name` as the link text or plain text.
*   Basic Tailwind link styling.

**Interaction:** Displays user name, conditionally linking to their profile.

*(Further view analysis pending...)*

### Pitch Status Banner Component (`pitch-status.blade.php`)

**File:** `resources/views/components/pitch-status.blade.php`

**Purpose:** Renders a prominent banner displaying a formatted pitch status with dynamic background and text colors.

**Usage:** Likely used via `@include('components.pitch-status', ['status' => $statusString, 'bgColor' => $tailwindBgClass, 'textColor' => $tailwindTextClass])`

**Input Props (Implicit):**
*   `$status`: The pitch status string (e.g., 'ready_for_review').
*   `$bgColor`: A Tailwind background color class (e.g., 'bg-blue-100').
*   `$textColor`: A Tailwind text color class (e.g., 'text-blue-800').

**Key Features:**
*   Formats the `$status` string using `ucwords(str_replace('_', ' ', $status))` for display.
*   Dynamically applies background and text color classes passed via `$bgColor` and `$textColor` using `@apply`.
*   Styled with Tailwind CSS.

**Interaction:** Purely a display component for showing status in a styled banner.

*(Further view analysis pending...)*

### Project Status Button Component (`project-status-button.blade.php`)

**File:** `resources/views/components/project-status-button.blade.php`

**Purpose:** Renders a styled badge or indicator for a project's status, including a relevant icon.

**Usage:** Likely used via `@include('components.project-status-button', ['status' => $project->status, 'type' => 'inline'])` or within Blade component context.

**Input Props (Implicit):**
*   `$status`: The project status string ('unpublished', 'open', 'review', 'completed', 'closed').
*   `$type`: Controls styling variations (e.g., 'top-right' for specific positioning/rounding, defaults to 'inline' styling).

**Key Features:**
*   **Status Mapping:** Uses a PHP `@switch` statement to map project `$status` strings to specific Tailwind CSS classes (`$colorClass`) and Font Awesome icon classes (`$iconClass`).
*   **Custom Colors:** Uses custom color names (e.g., `bg-statusOpen`, `shadow-statusOpen`) presumably defined in Tailwind config or CSS.
*   **Conditional Styling:** Applies different `border-radius` styles based on the `$type` variable.
*   **Display:** Renders a `<span>` containing the icon and the raw `$status` string.
*   **Styling:** Uses Tailwind CSS and Font Awesome.

**Interaction:** Purely a display component for project status indication.

*(Further view analysis pending...)*

### Invoice List View (`invoices.blade.php`)

**File:** `resources/views/billing/invoices.blade.php`

**Purpose:** Displays a paginated list of all the user's invoices.

**Layout:** Uses `x-app-layout` with a header slot ("Billing History") and a back link.

**Data:** Expects an `$invoices` collection (likely paginated Cashier Invoice objects). Uses `session`.

**Structure:**
*   Displays session messages.
*   Shows an empty state if no invoices exist.
*   Displays invoices in a table: Number, Date, Amount, Status (Paid/Unpaid), Type (Pitch Payment/Standard), Actions (View/Download).
*   "Type" column checks metadata for `pitch_id` and links to the project if applicable.
*   Provides "View" and "Download" links per invoice.

**Interaction:** Displays invoice list. Provides links to view/download invoices and navigate to related projects. Assumes pagination if data is paginated.

*(Further view analysis pending...)*

### Billing Diagnostic View (`diagnostic.blade.php`)

**File:** `resources/views/billing/diagnostic.blade.php`

**Purpose:** Developer/admin tool to diagnose invoice synchronization issues by comparing raw Stripe invoice data with Cashier's local count.

**Layout:** Uses `x-app-layout` with a header slot ("Invoice Diagnostic") and a back link.

**Data:** Expects `$stripeId`, `$cashierInvoiceCount`, `$rawInvoiceData` (array of raw Stripe invoices).

**Structure:**
*   Displays Stripe Customer ID, Cashier invoice count, raw Stripe invoice count.
*   Shows a table of raw invoice data fetched directly from Stripe.
*   Provides a list of debugging steps, including suggesting an Artisan command (`stripe:sync-invoices --all`).
*   Includes a "Return to Billing" link.

**Interaction:** Static display of diagnostic information and debugging steps.

*(Further view analysis pending...)*

### Payment Methods Management View (`payment-methods.blade.php`)

**File:** `resources/views/billing/payment-methods.blade.php`

**Purpose:** Allows users to view all saved payment methods, set a default, add new ones, and remove existing ones.

**Layout:** Uses `x-app-layout` with a header slot ("Payment Methods").

**Data:** Expects `$paymentMethods` (collection), `$defaultPaymentMethod`, `$intent` (Stripe SetupIntent). Uses `session`.

**Structure:**
*   Displays session messages.
*   "Add New Card" button toggles a hidden Stripe Elements form.
*   Lists existing payment methods with icon, brand, last 4, expiry.
*   Highlights the default method.
*   Provides "Make Default" button (POST form) for non-default methods.
*   Provides "Remove" button (DELETE form with JS confirm) for all methods.
*   Shows empty state message.
*   Footer links to Billing overview and Stripe Portal.

**JavaScript (`@push('scripts')`):**
*   Includes Stripe.js.
*   Initializes Stripe, mounts Card Element, handles errors.
*   Handles add card form submission using `confirmCardSetup` with SetupIntent, then submits to backend.
*   Toggles add card form visibility.

**Interaction:** Manages multiple payment methods, integrates Stripe Elements for adding cards, uses forms for setting default/removing.

*(Further view analysis pending...)*

### Checkout View (`checkout.blade.php`)

**File:** `resources/views/billing/checkout.blade.php`

**Purpose:** Provides a dedicated page for handling payments, likely for subscriptions or other purchases initiated via Cashier's `checkout`. Focuses solely on collecting payment details.

**Layout:** Uses `x-app-layout` with a header slot ("Checkout").

**Data:** Expects `$intent` (Stripe SetupIntent) and potentially `$priceId`. Uses `session`.

**Structure:**
*   Displays session messages.
*   Contains a single form POSTing to `billing.payment.process`.
*   Includes hidden `price_id` (if provided).
*   Includes Stripe Card Element (`#card-element`) and error display (`#card-errors`).
*   Submit button holds SetupIntent secret.
*   Provides a "Back to Billing" link.

**JavaScript (`@push('scripts')`):**
*   Includes Stripe.js.
*   Initializes Stripe, mounts Card Element, handles errors.
*   Handles form submission using `confirmCardSetup` with SetupIntent, then submits payment method ID and price ID to backend.

**Interaction:** Streamlined checkout page using Stripe Elements and SetupIntents to capture card details for payment processing.

*(Further view analysis pending...)*

### Detailed Invoice View (`invoice-details.blade.php` - Potentially Redundant)

**File:** `resources/views/billing/invoice-details.blade.php`

**Note:** This is a **full page view**, not the component `components/invoice-details.blade.php`. It appears redundant and potentially unused, as `invoice-show.blade.php` uses the component.

**Purpose:** Displays detailed invoice information, including line items, summary, payment method, and billing address.

**Layout:** Uses `x-app-layout` with header including back/download links.

**Data:** Expects `$invoice` (Cashier Invoice object). Uses `session`.

**Structure:**
*   Displays session messages.
*   Shows Invoice Header (Number, Date, Status).
*   Shows Summary (Subtotal, Tax, Discount, Total).
*   Shows Line Items table.
*   **Contains logic within the view** to fetch related Stripe PaymentMethod (for paid invoices) and Customer objects using the Stripe SDK.
*   Displays Payment Method details (Card Brand, Last 4, Expiry).
*   Displays Billing Details (Name, Email, Address).

**Interaction:** Static display of detailed invoice info. Includes logic within the view to fetch related Stripe data.

**Potential Issues:**
*   Likely redundant due to `invoice-show.blade.php` using the `x-invoice-details` component.
*   Contains business logic (Stripe SDK calls) within the view, violating separation of concerns.

*(Further view analysis pending...)*

## Email Views (`resources/views/emails/`)

### Email Test Form View (`test-form.blade.php`)

**File:** `resources/views/emails/test-form.blade.php`

**Purpose:** Provides a simple form for sending a test email (likely corresponds to `EmailController::showTestForm`).

**Layout:** Uses `x-app-layout` with a "Test Email" header.

**Data:** Uses `session` and `@error` directive.

**Structure:**
*   Displays session success/error messages.
*   Simple form POSTing to `email.test.send`.
*   Includes `@csrf`.
*   Input field for recipient email (`name="email"`) with validation error display.
*   "Send Test Email" submit button.

**Interaction:** User enters email and submits form to trigger sending of a predefined test email via the backend.

*(Further view analysis pending...)*

### Test Email Template (`test.blade.php`)

**File:** `resources/views/emails/test.blade.php`

**Purpose:** Defines the HTML structure and content for the test email sent via the `TestMail` Mailable.

**Layout:** Standalone HTML file with basic inline CSS. Does not use standard Laravel email components/markdown.

**Data:** Uses `date('Y')`.

**Structure:**
*   Simple centered container.
*   Static heading and text confirming email configuration (mentioning SES) is working.
*   Basic footer with copyright.

**Interaction:** Static email content.

*(Further view analysis pending...)*

### Jetstream Team Invitation Email (`team-invitation.blade.php`)

**File:** `resources/views/emails/team-invitation.blade.php`

**Purpose:** Standard Jetstream email template sent when a user is invited to a team.

**Layout:** Uses Laravel Markdown Mail components (`mail::message`, `mail::button`).

**Data:** Expects `$invitation` (Jetstream object) and `$acceptUrl`. Uses Fortify features check.

**Structure:**
*   States which team the user is invited to.
*   Conditionally includes a "Create Account" button if registration is enabled.
*   Includes an "Accept Invitation" button linking to `$acceptUrl`.
*   Includes discard instructions.

**Interaction:** Provides buttons to create account/accept invitation.

*(Further view analysis pending...)*

### Payment Receipt Email (`payment/receipt.blade.php`)

**File:** `resources/views/emails/payment/receipt.blade.php`

**Purpose:** Email template sent to both project owner and pitch creator upon successful payment or project completion (for free projects).

**Layout:** Standalone HTML file with basic inline CSS.

**Data:** Expects `$isFreeProject`, `$recipientName`, `$recipientType`, `$project`, `$pitch`, `$paymentDate`, `$invoiceId`, `$amount`.

**Structure:**
*   Mixpitch logo and title.
*   Greeting and text confirming payment/completion, tailored to recipient.
*   Summary box: Project Name, Pitch ID, Date, Invoice ID (if applicable), Amount/"Free Project".
*   Concluding message tailored to recipient.
*   Button linking to the web receipt view (`projects.pitches.payment.receipt`).
*   Standard footer.

**Interaction:** Confirmation email with link to web receipt.

*(Further view analysis pending...)*

## Filament Views (`resources/views/filament/`)

**Purpose:** Contains Blade views used by the Filament Admin Panel.

**Structure:** Organized into subdirectories like `pages`, `resources`, `widgets`, etc., mirroring Filament concepts.

**Analysis:** Views generally follow standard Filament patterns:
*   Use Filament layout components (e.g., `<x-filament::page>`).
*   Render widgets using `<x-filament-widgets::widgets />` based on configurations in PHP classes.
*   Embed Livewire components for specific functionality (e.g., `<livewire:email-test-form />` in `test-email-page.blade.php`).
*   Minimal custom Blade logic; complexity resides in corresponding `app/Filament/` PHP classes and embedded Livewire components.

**(Skipping detailed analysis of individual files as they follow standard Filament structure).**

*(Further view analysis pending...)*

## Vendor Views (`resources/views/vendor/`)

This directory is **empty**. No third-party package views have been published for customization.

*(Further view analysis pending...)*

## User Profile Views (`resources/views/user-profile/` & `resources/views/profile/`)

### Public Profile View (`user-profile/show.blade.php`)

**File:** `resources/views/user-profile/show.blade.php`

**Purpose:** Displays the public profile page for a given user.

**Layout:** Uses `x-app-layout`.

**Data:** Expects `$user`, `$canEdit` (boolean), `$projects` (collection), `$completedPitches` (collection).

**Structure:**
*   **Profile Header:** Avatar, Name, Username, Headline, Location, Website. Conditional "Edit Profile" button. Social media links, Tipjar link.
*   **Main Content Grid (2 columns):**
    *   **Left:** About (Bio, conditional Tipjar button), Skills/Equipment/Specialties (displayed as badges).
    *   **Right:** User's public Projects grid, Completed Pitches list.

**Interaction:** Displays profile info, projects, completed work. Includes external links (social, website, tipjar) and edit link for owner.

*(Further view analysis pending...)*

### Profile Edit View (Livewire) (`user-profile/edit-livewire.blade.php`)

**File:** `resources/views/user-profile/edit-livewire.blade.php`

**Purpose:** Container view that loads the `UserProfileEdit` Livewire component for editing the authenticated user's profile.

**Layout:** Uses `x-app-layout`.

**Data:** None passed directly; Livewire component fetches authenticated user.

**Structure:** Includes `<livewire:user-profile-edit />`.

**Interaction:** All logic handled by the embedded `UserProfileEdit` Livewire component.

*(Further view analysis pending...)*

### Profile Edit View (Standard Form - Potentially Redundant) (`user-profile/edit.blade.php`)

**File:** `resources/views/user-profile/edit.blade.php`

**Note:** This appears to be a standard HTML form implementation for profile editing, likely redundant due to the Livewire version (`user-profile/edit-livewire.blade.php` loading `UserProfileEdit`).

**Purpose:** Allows authenticated user to edit profile details via a standard form.

**Layout:** Uses `x-app-layout` with "Edit Profile" header and conditional public profile link.

**Data:** Expects `$user`. Uses `session`, `@error`, `old()`.

**Structure:**
*   Displays session messages and username setup prompt.
*   Standard HTML form POSTing to `profile.update` (PUT method).
*   Inputs for Username, Bio, Website, Location.
*   Inputs for Social Links (Twitter, Instagram, SoundCloud, Spotify, YouTube) using array format (`social_links[platform]`). Attempts to use `$this->getSocialUsername()` for pre-filling, suggesting it might have been intended for a Livewire component or requires a helper method.
*   "Save" button.

**Interaction:** Standard HTML form submission to `UserProfileController::update`.

**Redundancy:** Likely redundant/unused in favor of the Livewire implementation.

*(Further view analysis pending...)*

### Jetstream Profile Views (`resources/views/profile/`)

**Purpose:** Contains standard Jetstream views for user profile management (update info, password, 2FA, browser sessions, delete account).

**Structure:** The main view (`show.blade.php`) uses `x-app-layout` and conditionally includes various `@livewire('profile.*')` components based on enabled Jetstream/Fortify features. Other files in the directory are the Blade views for these embedded Livewire components.

**Interaction:** All logic is handled by the embedded Jetstream Livewire components.

**(Skipping detailed analysis of individual files as they are standard Jetstream components).**

*(Further view analysis pending...)*

## Pitch Files Views (`resources/views/pitch-files/`)

### Pitch File Show View (`show.blade.php`)

**File:** `resources/views/pitch-files/show.blade.php`

**Purpose:** Container view to display a single pitch file using the `PitchFilePlayer` Livewire component.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$file` (PitchFile model).

**Structure:**
*   Includes `<livewire:pitch-file-player :file="$file" />`.
*   Pushes basic CSS for waveform loading/timeline.
*   Pushes Wavesurfer.js library.

**Interaction:** All logic handled by the embedded `PitchFilePlayer` Livewire component.

*(Further view analysis pending...)*

## Authentication Views (`resources/views/auth/`)

**Purpose:** Contains standard Laravel Fortify views for handling authentication flows (login, registration, password reset, two-factor auth, etc.).

**Structure:** Views use `x-guest-layout` and standard Jetstream/Fortify Blade components (e.g., `x-authentication-card`). They correspond to Fortify's backend routes and actions.

**Interaction:** Standard HTML forms submitting to Fortify controllers/actions.

**(Skipping detailed analysis of individual files as they are standard Fortify views).**

*(Further view analysis pending...)*

## API Token Views (`resources/views/api/`)

**Purpose:** Contains standard Jetstream views for API token management.

**Structure:** The main view (`index.blade.php`) uses `x-app-layout` and includes the `@livewire('api.api-token-manager')` component. The other file (`api-token-manager.blade.php`) is the view for that Livewire component.

**Interaction:** All logic handled by the embedded Jetstream Livewire component.

**(Skipping detailed analysis as they are standard Jetstream components).**

*(Further view analysis pending...)*

## Projects Views (`resources/views/projects/`)

### Public Project Show View (`project.blade.php`)

**File:** `resources/views/projects/project.blade.php`

**Purpose:** Displays the public detail page for a single project.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project` (with relations), `$canPitch` (boolean), `$userPitch` (Pitch model or null). Uses `Auth`.

**Structure:**
*   **Header Card:** Project image (Alpine lightbox), preview track audio player (Livewire), Project Name, Artist, Owner (`x-user-link`), Status (`x-project-status-button`), Type, Deadline. Conditional buttons (Manage Project / Start Pitch / View Pitch).
*   **Details Section:** Collaboration badges, Budget, Description, Notes.

**Interaction:** Displays project details. Conditional actions for owner (Manage) or others (Start/View Pitch). Pitch initiation uses JS modal (`openPitchTermsModal()`). Includes audio player and image lightbox.

*(Further view analysis pending...)*

### Project Edit View (Standard Form - Potentially Redundant) (`edit.blade.php`)

**File:** `resources/views/projects/edit.blade.php`

**Note:** Likely redundant due to the `ManageProject` Livewire component handling project editing and file management.

**Purpose:** Allows editing project details and managing files via standard HTML forms.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project` (with relations). Uses `old()`.

**Structure:**
*   **Header:** Displays project image (clickable for upload via JS), inputs for Name, Description, Genre, Status.
*   **Form:** Standard HTML form POSTing to `projects.update` (PUT method), `enctype="multipart/form-data"`.
*   **File Management:** Lists existing files with Delete buttons (individual DELETE forms triggered by JS). Links to a separate `projects.createStep2` route for uploads.

**JavaScript:** Includes `loadFile` for image preview and event listener for delete buttons.

**Interaction:** Standard form submission for edits. File deletion via separate forms triggered by JS. Links to potentially deprecated upload step.

**Redundancy:** Likely redundant/unused in favor of the `ManageProject` Livewire implementation.

### Project Index View (`index.blade.php` - Livewire Wrapper)

**File:** `resources/views/projects/index.blade.php`

**Purpose:** Container view for the public project listing.

**Layout:** Extends `components.layouts.app`.

**Structure:** Includes `<livewire:projects-component />`. Assumes the listing logic is handled by this Livewire component.

### Project Upload (Step 2) View (`create_step2.blade.php` - Likely Deprecated)

**File:** `resources/views/projects/create_step2.blade.php`

**Purpose:** Provides a Dropzone.js interface for uploading files as part of a multi-step project creation/edit process.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project`.

**Structure:** Includes Dropzone CSS/JS. Contains Dropzone form POSTing to `projects.storeStep2`. Includes Back/Finish links.

**JavaScript:** Initializes Dropzone.

**Redundancy:** Uses Dropzone and separate upload step, contrasting with integrated S3 uploads in `ManageProject`. Highly likely deprecated.

### Upload Project View (`upload-project.blade.php` - TrackController related)

**File:** `resources/views/projects/upload-project.blade.php`

**Purpose:** Container view, likely related to the `TrackController` functionality.

**Layout:** Extends `components.layouts.app`.

**Structure:** Includes `<livewire:upload-project-component />`. Logic is assumed to be in this Livewire component.

*(Further view analysis pending...)*

## Components (`resources/views/components/`)

### Invoice Details Component (`invoice-details.blade.php`)

**File:** `resources/views/components/invoice-details.blade.php`

**Purpose:** Renders a detailed view of a single invoice, likely using data fetched from Stripe.

**Usage:** Expected to be used like `<x-invoice-details :invoice="$invoiceObject" :view-all-url="route(...)" />`

**Input Props:**
*   `$invoice`: An object containing invoice data (ID/number, status, date, total, description, line items). Structure suggests compatibility with Stripe API responses or formatted objects from `InvoiceService`.
*   `$viewAllUrl` (optional): A URL string for a 'View all invoices' link.

**Key Features:**
*   Displays invoice number, paid/unpaid status badge, date, and total amount.
*   Lists line items from `$invoice->lines->data` or shows a single summary line.
*   Provides a 'Download PDF' link to `route('billing.invoice.download', $invoice->id)`.
*   Optionally provides a 'View all invoices' link.
*   Styled with Tailwind CSS.

**Interaction:** Primarily a display component formatting invoice data. Provides navigation links for downloading and viewing other invoices.

*(Further view analysis pending...)*

### Update Pitch Status Buttons Component (`update-pitch-status.blade.php`)

**File:** `resources/views/components/update-pitch-status.blade.php`

**Purpose:** Renders action buttons for changing a pitch's status based on its current state.
**Note: This component appears highly problematic and likely redundant/deprecated.** Its logic seems to belong within the `UpdatePitchStatus` Livewire component's view (`resources/views/livewire/pitch/component/update-pitch-status.blade.php`). This Blade component bypasses the Livewire component's actions and service layer calls in many cases.

**Usage:** Likely intended usage `<x-update-pitch-status :pitch="$pitch" />`

**Input Props:**
*   `$pitch`: The `Pitch` model.

**Key Features & Issues:**
*   **Conditional Logic:** Uses extensive `@if/@elseif` based on `$pitch->status` to determine which actions/buttons to show.
*   **Direct Form Submissions (Issue):** For several statuses (Pending, In Progress, Approved, Denied, Revisions Requested, Completed), it renders HTML forms that POST directly to controller routes (`projects.pitches.change-status`, `projects.pitches.return-to-approved`). This bypasses the `UpdatePitchStatus` Livewire component and the `PitchWorkflowService`, potentially leading to inconsistent state management and authorization issues.
*   **JavaScript Modal Triggers (Partial Alignment):** For the `READY_FOR_REVIEW` status, it renders buttons that trigger JavaScript functions (`openApproveModal`, etc.) presumably defined elsewhere to interact with modals (`<x-pitch-action-modals />`). This aligns partially with the Livewire component's modal interaction but still passes controller routes directly to JS instead of using Livewire actions.
*   **Includes Modals:** Includes `<x-pitch-action-modals />`.

**Interaction:** Renders conditional buttons/forms. Interacts via direct form POSTs or JavaScript calls, largely bypassing the corresponding Livewire component. Includes a modal component.

**Recommendation:** This component should be reviewed and likely removed. Its logic needs to be consolidated within the `UpdatePitchStatus` Livewire component and its associated view to ensure consistent use of the `PitchWorkflowService` and proper state management.

*(Further view analysis pending...)*

### Pitch Action Modals Component (`pitch-action-modals.blade.php`)

**File:** `resources/views/components/pitch-action-modals.blade.php`

**Purpose:** Defines the HTML structure for modals used for Approve, Deny, and Request Revisions actions on pitches.

**Usage:** Included by other components, e.g., `<x-pitch-action-modals />` (as seen in `update-pitch-status.blade.php`).

**Input Props:** None.

**Key Features:**
*   **Structure:** Contains three distinct modal sections (`#approveModal`, `#denyModal`, `#revisionsModal`), initially hidden.
*   **Forms:** Each modal contains a basic, hidden HTML form (`#approveForm`, etc.) with only `@csrf`. JavaScript is expected to set the `action` URL and potentially add data before submission.
*   **Inputs:** Provides textareas for denial reason (`#denyReason`) and revision requests (`#revisionsRequested`).
*   **Buttons:** Includes Cancel buttons (`onclick="closeModal(...)"`) and confirmation buttons (`#approveSubmitBtn`, etc.) presumably handled by JavaScript.
*   **JavaScript Dependency:** Includes `<script src="{{ asset('js/pitch-modals.js') }}"></script>`, indicating all modal interaction logic (opening, closing, submitting the hidden forms) resides in this external JS file.
*   **Styling:** Uses Tailwind CSS.

**Interaction:** Provides the static modal HTML. Relies entirely on `pitch-modals.js` (and functions like `openApproveModal`, `closeModal`) for display logic and form submission, targeting controller routes directly. This approach is less integrated with Livewire/Alpine compared to using Livewire components for modal state and actions.

**Note:** The reliance on external JS to manipulate and submit hidden forms directly to controller routes is part of the potentially problematic pattern identified in `components/update-pitch-status.blade.php`.

*(Further view analysis pending...)*

### Pitch Terms Modal Component (`pitch-terms-modal.blade.php`)

**File:** `resources/views/components/pitch-terms-modal.blade.php`

**Purpose:** Displays a modal with terms highlights that must be agreed to before initiating a pitch creation request.

**Usage:** Likely included on project detail pages, e.g., `<x-pitch-terms-modal :project="$project" />`. Triggered via JavaScript (`openPitchTermsModal()`).

**Input Props:**
*   `$project`: The `Project` model the user wants to pitch for.

**Key Features:**
*   **Structure:** Defines a modal (`#pitch-terms-modal`) with header, body (terms highlights, full terms link, agreement checkbox), and footer.
*   **Form:** Contains a hidden HTML form (`#pitch-create-form`) targeting `route('projects.pitches.store')` via POST. Includes `@csrf` and `$project->id`.
*   **Agreement:** Requires user to check an 'Agree to Terms' checkbox (`#agree_terms`).
*   **JavaScript:** Includes inline JS defining global functions (`openPitchTermsModal`, `closePitchTermsModal`, `submitPitchForm`) for modal control and form submission. `submitPitchForm` validates checkbox before submitting.
*   **Styling:** Uses Tailwind CSS.

**Interaction:** Provides the UI flow for agreeing to terms before creating a pitch. Uses inline JavaScript for modal display and conditional form submission. Submits directly to a controller route upon confirmation.

*(Further view analysis pending...)*

### User Link Component (`user-link.blade.php`)

**File:** `resources/views/components/user-link.blade.php`

**Purpose:** Renders a user's name, providing a link to their public profile if they have a username.

**Usage:** `<x-user-link :user="$userObject" />`

**Input Props:**
*   `$user`: The `User` model instance.

**Key Features:**
*   Conditionally renders an anchor tag linking to `route('profile.username', ['username' => $user->username])` if `$user->username` is set.
*   Displays the `$user->name` as the link text or plain text.
*   Basic Tailwind link styling.

**Interaction:** Displays user name, conditionally linking to their profile.

*(Further view analysis pending...)*

### Pitch Status Banner Component (`pitch-status.blade.php`)

**File:** `resources/views/components/pitch-status.blade.php`

**Purpose:** Renders a prominent banner displaying a formatted pitch status with dynamic background and text colors.

**Usage:** Likely used via `@include('components.pitch-status', ['status' => $statusString, 'bgColor' => $tailwindBgClass, 'textColor' => $tailwindTextClass])`

**Input Props (Implicit):**
*   `$status`: The pitch status string (e.g., 'ready_for_review').
*   `$bgColor`: A Tailwind background color class (e.g., 'bg-blue-100').
*   `$textColor`: A Tailwind text color class (e.g., 'text-blue-800').

**Key Features:**
*   Formats the `$status` string using `ucwords(str_replace('_', ' ', $status))` for display.
*   Dynamically applies background and text color classes passed via `$bgColor` and `$textColor` using `@apply`.
*   Styled with Tailwind CSS.

**Interaction:** Purely a display component for showing status in a styled banner.

*(Further view analysis pending...)*

### Project Status Button Component (`project-status-button.blade.php`)

**File:** `resources/views/components/project-status-button.blade.php`

**Purpose:** Renders a styled badge or indicator for a project's status, including a relevant icon.

**Usage:** Likely used via `@include('components.project-status-button', ['status' => $project->status, 'type' => 'inline'])` or within Blade component context.

**Input Props (Implicit):**
*   `$status`: The project status string ('unpublished', 'open', 'review', 'completed', 'closed').
*   `$type`: Controls styling variations (e.g., 'top-right' for specific positioning/rounding, defaults to 'inline' styling).

**Key Features:**
*   **Status Mapping:** Uses a PHP `@switch` statement to map project `$status` strings to specific Tailwind CSS classes (`$colorClass`) and Font Awesome icon classes (`$iconClass`).
*   **Custom Colors:** Uses custom color names (e.g., `bg-statusOpen`, `shadow-statusOpen`) presumably defined in Tailwind config or CSS.
*   **Conditional Styling:** Applies different `border-radius` styles based on the `$type` variable.
*   **Display:** Renders a `<span>` containing the icon and the raw `$status` string.
*   **Styling:** Uses Tailwind CSS and Font Awesome.

**Interaction:** Purely a display component for project status indication.

*(Further view analysis pending...)*

### Invoice List View (`invoices.blade.php`)

**File:** `resources/views/billing/invoices.blade.php`

**Purpose:** Displays a paginated list of all the user's invoices.

**Layout:** Uses `x-app-layout` with a header slot ("Billing History") and a back link.

**Data:** Expects an `$invoices` collection (likely paginated Cashier Invoice objects). Uses `session`.

**Structure:**
*   Displays session messages.
*   Shows an empty state if no invoices exist.
*   Displays invoices in a table: Number, Date, Amount, Status (Paid/Unpaid), Type (Pitch Payment/Standard), Actions (View/Download).
*   "Type" column checks metadata for `pitch_id` and links to the project if applicable.
*   Provides "View" and "Download" links per invoice.

**Interaction:** Displays invoice list. Provides links to view/download invoices and navigate to related projects. Assumes pagination if data is paginated.

*(Further view analysis pending...)*

### Billing Diagnostic View (`diagnostic.blade.php`)

**File:** `resources/views/billing/diagnostic.blade.php`

**Purpose:** Developer/admin tool to diagnose invoice synchronization issues by comparing raw Stripe invoice data with Cashier's local count.

**Layout:** Uses `x-app-layout` with a header slot ("Invoice Diagnostic") and a back link.

**Data:** Expects `$stripeId`, `$cashierInvoiceCount`, `$rawInvoiceData` (array of raw Stripe invoices).

**Structure:**
*   Displays Stripe Customer ID, Cashier invoice count, raw Stripe invoice count.
*   Shows a table of raw invoice data fetched directly from Stripe.
*   Provides a list of debugging steps, including suggesting an Artisan command (`stripe:sync-invoices --all`).
*   Includes a "Return to Billing" link.

**Interaction:** Static display of diagnostic information and debugging steps.

*(Further view analysis pending...)*

### Payment Methods Management View (`payment-methods.blade.php`)

**File:** `resources/views/billing/payment-methods.blade.php`

**Purpose:** Allows users to view all saved payment methods, set a default, add new ones, and remove existing ones.

**Layout:** Uses `x-app-layout` with a header slot ("Payment Methods").

**Data:** Expects `$paymentMethods` (collection), `$defaultPaymentMethod`, `$intent` (Stripe SetupIntent). Uses `session`.

**Structure:**
*   Displays session messages.
*   "Add New Card" button toggles a hidden Stripe Elements form.
*   Lists existing payment methods with icon, brand, last 4, expiry.
*   Highlights the default method.
*   Provides "Make Default" button (POST form) for non-default methods.
*   Provides "Remove" button (DELETE form with JS confirm) for all methods.
*   Shows empty state message.
*   Footer links to Billing overview and Stripe Portal.

**JavaScript (`@push('scripts')`):**
*   Includes Stripe.js.
*   Initializes Stripe, mounts Card Element, handles errors.
*   Handles add card form submission using `confirmCardSetup` with SetupIntent, then submits to backend.
*   Toggles add card form visibility.

**Interaction:** Manages multiple payment methods, integrates Stripe Elements for adding cards, uses forms for setting default/removing.

*(Further view analysis pending...)*

### Checkout View (`checkout.blade.php`)

**File:** `resources/views/billing/checkout.blade.php`

**Purpose:** Provides a dedicated page for handling payments, likely for subscriptions or other purchases initiated via Cashier's `checkout`. Focuses solely on collecting payment details.

**Layout:** Uses `x-app-layout` with a header slot ("Checkout").

**Data:** Expects `$intent` (Stripe SetupIntent) and potentially `$priceId`. Uses `session`.

**Structure:**
*   Displays session messages.
*   Contains a single form POSTing to `billing.payment.process`.
*   Includes hidden `price_id` (if provided).
*   Includes Stripe Card Element (`#card-element`) and error display (`#card-errors`).
*   Submit button holds SetupIntent secret.
*   Provides a "Back to Billing" link.

**JavaScript (`@push('scripts')`):**
*   Includes Stripe.js.
*   Initializes Stripe, mounts Card Element, handles errors.
*   Handles form submission using `confirmCardSetup` with SetupIntent, then submits payment method ID and price ID to backend.

**Interaction:** Streamlined checkout page using Stripe Elements and SetupIntents to capture card details for payment processing.

*(Further view analysis pending...)*

### Detailed Invoice View (`invoice-details.blade.php` - Potentially Redundant)

**File:** `resources/views/billing/invoice-details.blade.php`

**Note:** This is a **full page view**, not the component `components/invoice-details.blade.php`. It appears redundant and potentially unused, as `invoice-show.blade.php` uses the component.

**Purpose:** Displays detailed invoice information, including line items, summary, payment method, and billing address.

**Layout:** Uses `x-app-layout` with header including back/download links.

**Data:** Expects `$invoice` (Cashier Invoice object). Uses `session`.

**Structure:**
*   Displays session messages.
*   Shows Invoice Header (Number, Date, Status).
*   Shows Summary (Subtotal, Tax, Discount, Total).
*   Shows Line Items table.
*   **Contains logic within the view** to fetch related Stripe PaymentMethod (for paid invoices) and Customer objects using the Stripe SDK.
*   Displays Payment Method details (Card Brand, Last 4, Expiry).
*   Displays Billing Details (Name, Email, Address).

**Interaction:** Static display of detailed invoice info. Includes logic within the view to fetch related Stripe data.

**Potential Issues:**
*   Likely redundant due to `invoice-show.blade.php` using the `x-invoice-details` component.
*   Contains business logic (Stripe SDK calls) within the view, violating separation of concerns.

*(Further view analysis pending...)*

## Email Views (`resources/views/emails/`)

### Email Test Form View (`test-form.blade.php`)

**File:** `resources/views/emails/test-form.blade.php`

**Purpose:** Provides a simple form for sending a test email (likely corresponds to `EmailController::showTestForm`).

**Layout:** Uses `x-app-layout` with a "Test Email" header.

**Data:** Uses `session` and `@error` directive.

**Structure:**
*   Displays session success/error messages.
*   Simple form POSTing to `email.test.send`.
*   Includes `@csrf`.
*   Input field for recipient email (`name="email"`) with validation error display.
*   "Send Test Email" submit button.

**Interaction:** User enters email and submits form to trigger sending of a predefined test email via the backend.

*(Further view analysis pending...)*

### Test Email Template (`test.blade.php`)

**File:** `resources/views/emails/test.blade.php`

**Purpose:** Defines the HTML structure and content for the test email sent via the `TestMail` Mailable.

**Layout:** Standalone HTML file with basic inline CSS. Does not use standard Laravel email components/markdown.

**Data:** Uses `date('Y')`.

**Structure:**
*   Simple centered container.
*   Static heading and text confirming email configuration (mentioning SES) is working.
*   Basic footer with copyright.

**Interaction:** Static email content.

*(Further view analysis pending...)*

### Jetstream Team Invitation Email (`team-invitation.blade.php`)

**File:** `resources/views/emails/team-invitation.blade.php`

**Purpose:** Standard Jetstream email template sent when a user is invited to a team.

**Layout:** Uses Laravel Markdown Mail components (`mail::message`, `mail::button`).

**Data:** Expects `$invitation` (Jetstream object) and `$acceptUrl`. Uses Fortify features check.

**Structure:**
*   States which team the user is invited to.
*   Conditionally includes a "Create Account" button if registration is enabled.
*   Includes an "Accept Invitation" button linking to `$acceptUrl`.
*   Includes discard instructions.

**Interaction:** Provides buttons to create account/accept invitation.

*(Further view analysis pending...)*

### Payment Receipt Email (`payment/receipt.blade.php`)

**File:** `resources/views/emails/payment/receipt.blade.php`

**Purpose:** Email template sent to both project owner and pitch creator upon successful payment or project completion (for free projects).

**Layout:** Standalone HTML file with basic inline CSS.

**Data:** Expects `$isFreeProject`, `$recipientName`, `$recipientType`, `$project`, `$pitch`, `$paymentDate`, `$invoiceId`, `$amount`.

**Structure:**
*   Mixpitch logo and title.
*   Greeting and text confirming payment/completion, tailored to recipient.
*   Summary box: Project Name, Pitch ID, Date, Invoice ID (if applicable), Amount/"Free Project".
*   Concluding message tailored to recipient.
*   Button linking to the web receipt view (`projects.pitches.payment.receipt`).
*   Standard footer.

**Interaction:** Confirmation email with link to web receipt.

*(Further view analysis pending...)*

## Filament Views (`resources/views/filament/`)

**Purpose:** Contains Blade views used by the Filament Admin Panel.

**Structure:** Organized into subdirectories like `pages`, `resources`, `widgets`, etc., mirroring Filament concepts.

**Analysis:** Views generally follow standard Filament patterns:
*   Use Filament layout components (e.g., `<x-filament::page>`).
*   Render widgets using `<x-filament-widgets::widgets />` based on configurations in PHP classes.
*   Embed Livewire components for specific functionality (e.g., `<livewire:email-test-form />` in `test-email-page.blade.php`).
*   Minimal custom Blade logic; complexity resides in corresponding `app/Filament/` PHP classes and embedded Livewire components.

**(Skipping detailed analysis of individual files as they follow standard Filament structure).**

*(Further view analysis pending...)*

## Vendor Views (`resources/views/vendor/`)

This directory is **empty**. No third-party package views have been published for customization.

*(Further view analysis pending...)*

## User Profile Views (`resources/views/user-profile/` & `resources/views/profile/`)

### Public Profile View (`user-profile/show.blade.php`)

**File:** `resources/views/user-profile/show.blade.php`

**Purpose:** Displays the public profile page for a given user.

**Layout:** Uses `x-app-layout`.

**Data:** Expects `$user`, `$canEdit` (boolean), `$projects` (collection), `$completedPitches` (collection).

**Structure:**
*   **Profile Header:** Avatar, Name, Username, Headline, Location, Website. Conditional "Edit Profile" button. Social media links, Tipjar link.
*   **Main Content Grid (2 columns):**
    *   **Left:** About (Bio, conditional Tipjar button), Skills/Equipment/Specialties (displayed as badges).
    *   **Right:** User's public Projects grid, Completed Pitches list.

**Interaction:** Displays profile info, projects, completed work. Includes external links (social, website, tipjar) and edit link for owner.

*(Further view analysis pending...)*

### Profile Edit View (Livewire) (`user-profile/edit-livewire.blade.php`)

**File:** `resources/views/user-profile/edit-livewire.blade.php`

**Purpose:** Container view that loads the `UserProfileEdit` Livewire component for editing the authenticated user's profile.

**Layout:** Uses `x-app-layout`.

**Data:** None passed directly; Livewire component fetches authenticated user.

**Structure:** Includes `<livewire:user-profile-edit />`.

**Interaction:** All logic handled by the embedded `UserProfileEdit` Livewire component.

*(Further view analysis pending...)*

### Profile Edit View (Standard Form - Potentially Redundant) (`user-profile/edit.blade.php`)

**File:** `resources/views/user-profile/edit.blade.php`

**Note:** This appears to be a standard HTML form implementation for profile editing, likely redundant due to the Livewire version (`user-profile/edit-livewire.blade.php` loading `UserProfileEdit`).

**Purpose:** Allows authenticated user to edit profile details via a standard form.

**Layout:** Uses `x-app-layout` with "Edit Profile" header and conditional public profile link.

**Data:** Expects `$user`. Uses `session`, `@error`, `old()`.

**Structure:**
*   Displays session messages and username setup prompt.
*   Standard HTML form POSTing to `profile.update` (PUT method).
*   Inputs for Username, Bio, Website, Location.
*   Inputs for Social Links (Twitter, Instagram, SoundCloud, Spotify, YouTube) using array format (`social_links[platform]`). Attempts to use `$this->getSocialUsername()` for pre-filling, suggesting it might have been intended for a Livewire component or requires a helper method.
*   "Save" button.

**Interaction:** Standard HTML form submission to `UserProfileController::update`.

**Redundancy:** Likely redundant/unused in favor of the Livewire implementation.

*(Further view analysis pending...)*

### Jetstream Profile Views (`resources/views/profile/`)

**Purpose:** Contains standard Jetstream views for user profile management (update info, password, 2FA, browser sessions, delete account).

**Structure:** The main view (`show.blade.php`) uses `x-app-layout` and conditionally includes various `@livewire('profile.*')` components based on enabled Jetstream/Fortify features. Other files in the directory are the Blade views for these embedded Livewire components.

**Interaction:** All logic is handled by the embedded Jetstream Livewire components.

**(Skipping detailed analysis of individual files as they are standard Jetstream components).**

*(Further view analysis pending...)*

## Pitch Files Views (`resources/views/pitch-files/`)

### Pitch File Show View (`show.blade.php`)

**File:** `resources/views/pitch-files/show.blade.php`

**Purpose:** Container view to display a single pitch file using the `PitchFilePlayer` Livewire component.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$file` (PitchFile model).

**Structure:**
*   Includes `<livewire:pitch-file-player :file="$file" />`.
*   Pushes basic CSS for waveform loading/timeline.
*   Pushes Wavesurfer.js library.

**Interaction:** All logic handled by the embedded `PitchFilePlayer` Livewire component.

*(Further view analysis pending...)*

## Authentication Views (`resources/views/auth/`)

**Purpose:** Contains standard Laravel Fortify views for handling authentication flows (login, registration, password reset, two-factor auth, etc.).

**Structure:** Views use `x-guest-layout` and standard Jetstream/Fortify Blade components (e.g., `x-authentication-card`). They correspond to Fortify's backend routes and actions.

**Interaction:** Standard HTML forms submitting to Fortify controllers/actions.

**(Skipping detailed analysis of individual files as they are standard Fortify views).**

*(Further view analysis pending...)*

## API Token Views (`resources/views/api/`)

**Purpose:** Contains standard Jetstream views for API token management.

**Structure:** The main view (`index.blade.php`) uses `x-app-layout` and includes the `@livewire('api.api-token-manager')` component. The other file (`api-token-manager.blade.php`) is the view for that Livewire component.

**Interaction:** All logic handled by the embedded Jetstream Livewire component.

**(Skipping detailed analysis as they are standard Jetstream components).**

*(Further view analysis pending...)*

## Projects Views (`resources/views/projects/`)

### Public Project Show View (`project.blade.php`)

**File:** `resources/views/projects/project.blade.php`

**Purpose:** Displays the public detail page for a single project.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project` (with relations), `$canPitch` (boolean), `$userPitch` (Pitch model or null). Uses `Auth`.

**Structure:**
*   **Header Card:** Project image (Alpine lightbox), preview track audio player (Livewire), Project Name, Artist, Owner (`x-user-link`), Status (`x-project-status-button`), Type, Deadline. Conditional buttons (Manage Project / Start Pitch / View Pitch).
*   **Details Section:** Collaboration badges, Budget, Description, Notes.

**Interaction:** Displays project details. Conditional actions for owner (Manage) or others (Start/View Pitch). Pitch initiation uses JS modal (`openPitchTermsModal()`). Includes audio player and image lightbox.

*(Further view analysis pending...)*

### Project Edit View (Standard Form - Potentially Redundant) (`edit.blade.php`)

**File:** `resources/views/projects/edit.blade.php`

**Note:** Likely redundant due to the `ManageProject` Livewire component handling project editing and file management.

**Purpose:** Allows editing project details and managing files via standard HTML forms.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project` (with relations). Uses `old()`.

**Structure:**
*   **Header:** Displays project image (clickable for upload via JS), inputs for Name, Description, Genre, Status.
*   **Form:** Standard HTML form POSTing to `projects.update` (PUT method), `enctype="multipart/form-data"`.
*   **File Management:** Lists existing files with Delete buttons (individual DELETE forms triggered by JS). Links to a separate `projects.createStep2` route for uploads.

**JavaScript:** Includes `loadFile` for image preview and event listener for delete buttons.

**Interaction:** Standard form submission for edits. File deletion via separate forms triggered by JS. Links to potentially deprecated upload step.

**Redundancy:** Likely redundant/unused in favor of the `ManageProject` Livewire implementation.

### Project Index View (`index.blade.php` - Livewire Wrapper)

**File:** `resources/views/projects/index.blade.php`

**Purpose:** Container view for the public project listing.

**Layout:** Extends `components.layouts.app`.

**Structure:** Includes `<livewire:projects-component />`. Assumes the listing logic is handled by this Livewire component.

### Project Upload (Step 2) View (`create_step2.blade.php` - Likely Deprecated)

**File:** `resources/views/projects/create_step2.blade.php`

**Purpose:** Provides a Dropzone.js interface for uploading files as part of a multi-step project creation/edit process.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project`.

**Structure:** Includes Dropzone CSS/JS. Contains Dropzone form POSTing to `projects.storeStep2`. Includes Back/Finish links.

**JavaScript:** Initializes Dropzone.

**Redundancy:** Uses Dropzone and separate upload step, contrasting with integrated S3 uploads in `ManageProject`. Highly likely deprecated.

### Upload Project View (`upload-project.blade.php` - TrackController related)

**File:** `resources/views/projects/upload-project.blade.php`

**Purpose:** Container view, likely related to the `TrackController` functionality.

**Layout:** Extends `components.layouts.app`.

**Structure:** Includes `<livewire:upload-project-component />`. Logic is assumed to be in this Livewire component.

*(Further view analysis pending...)*

## Components (`resources/views/components/`)

### Invoice Details Component (`invoice-details.blade.php`)

**File:** `resources/views/components/invoice-details.blade.php`

**Purpose:** Renders a detailed view of a single invoice, likely using data fetched from Stripe.

**Usage:** Expected to be used like `<x-invoice-details :invoice="$invoiceObject" :view-all-url="route(...)" />`

**Input Props:**
*   `$invoice`: An object containing invoice data (ID/number, status, date, total, description, line items). Structure suggests compatibility with Stripe API responses or formatted objects from `InvoiceService`.
*   `$viewAllUrl` (optional): A URL string for a 'View all invoices' link.

**Key Features:**
*   Displays invoice number, paid/unpaid status badge, date, and total amount.
*   Lists line items from `$invoice->lines->data` or shows a single summary line.
*   Provides a 'Download PDF' link to `route('billing.invoice.download', $invoice->id)`.
*   Optionally provides a 'View all invoices' link.
*   Styled with Tailwind CSS.

**Interaction:** Primarily a display component formatting invoice data. Provides navigation links for downloading and viewing other invoices.

*(Further view analysis pending...)*

### Update Pitch Status Buttons Component (`update-pitch-status.blade.php`)

**File:** `resources/views/components/update-pitch-status.blade.php`

**Purpose:** Renders action buttons for changing a pitch's status based on its current state.
**Note: This component appears highly problematic and likely redundant/deprecated.** Its logic seems to belong within the `UpdatePitchStatus` Livewire component's view (`resources/views/livewire/pitch/component/update-pitch-status.blade.php`). This Blade component bypasses the Livewire component's actions and service layer calls in many cases.

**Usage:** Likely intended usage `<x-update-pitch-status :pitch="$pitch" />`

**Input Props:**
*   `$pitch`: The `Pitch` model.

**Key Features & Issues:**
*   **Conditional Logic:** Uses extensive `@if/@elseif` based on `$pitch->status` to determine which actions/buttons to show.
*   **Direct Form Submissions (Issue):** For several statuses (Pending, In Progress, Approved, Denied, Revisions Requested, Completed), it renders HTML forms that POST directly to controller routes (`projects.pitches.change-status`, `projects.pitches.return-to-approved`). This bypasses the `UpdatePitchStatus` Livewire component and the `PitchWorkflowService`, potentially leading to inconsistent state management and authorization issues.
*   **JavaScript Modal Triggers (Partial Alignment):** For the `READY_FOR_REVIEW` status, it renders buttons that trigger JavaScript functions (`openApproveModal`, etc.) presumably defined elsewhere to interact with modals (`<x-pitch-action-modals />`). This aligns partially with the Livewire component's modal interaction but still passes controller routes directly to JS instead of using Livewire actions.
*   **Includes Modals:** Includes `<x-pitch-action-modals />`.

**Interaction:** Renders conditional buttons/forms. Interacts via direct form POSTs or JavaScript calls, largely bypassing the corresponding Livewire component. Includes a modal component.

**Recommendation:** This component should be reviewed and likely removed. Its logic needs to be consolidated within the `UpdatePitchStatus` Livewire component and its associated view to ensure consistent use of the `PitchWorkflowService` and proper state management.

*(Further view analysis pending...)*

### Pitch Action Modals Component (`pitch-action-modals.blade.php`)

**File:** `resources/views/components/pitch-action-modals.blade.php`

**Purpose:** Defines the HTML structure for modals used for Approve, Deny, and Request Revisions actions on pitches.

**Usage:** Included by other components, e.g., `<x-pitch-action-modals />` (as seen in `update-pitch-status.blade.php`).

**Input Props:** None.

**Key Features:**
*   **Structure:** Contains three distinct modal sections (`#approveModal`, `#denyModal`, `#revisionsModal`), initially hidden.
*   **Forms:** Each modal contains a basic, hidden HTML form (`#approveForm`, etc.) with only `@csrf`. JavaScript is expected to set the `action` URL and potentially add data before submission.
*   **Inputs:** Provides textareas for denial reason (`#denyReason`) and revision requests (`#revisionsRequested`).
*   **Buttons:** Includes Cancel buttons (`onclick="closeModal(...)"`) and confirmation buttons (`#approveSubmitBtn`, etc.) presumably handled by JavaScript.
*   **JavaScript Dependency:** Includes `<script src="{{ asset('js/pitch-modals.js') }}"></script>`, indicating all modal interaction logic (opening, closing, submitting the hidden forms) resides in this external JS file.
*   **Styling:** Uses Tailwind CSS.

**Interaction:** Provides the static modal HTML. Relies entirely on `pitch-modals.js` (and functions like `openApproveModal`, `closeModal`) for display logic and form submission, targeting controller routes directly. This approach is less integrated with Livewire/Alpine compared to using Livewire components for modal state and actions.

**Note:** The reliance on external JS to manipulate and submit hidden forms directly to controller routes is part of the potentially problematic pattern identified in `components/update-pitch-status.blade.php`.

*(Further view analysis pending...)*

### Pitch Terms Modal Component (`pitch-terms-modal.blade.php`)

**File:** `resources/views/components/pitch-terms-modal.blade.php`

**Purpose:** Displays a modal with terms highlights that must be agreed to before initiating a pitch creation request.

**Usage:** Likely included on project detail pages, e.g., `<x-pitch-terms-modal :project="$project" />`. Triggered via JavaScript (`openPitchTermsModal()`).

**Input Props:**
*   `$project`: The `Project` model the user wants to pitch for.

**Key Features:**
*   **Structure:** Defines a modal (`#pitch-terms-modal`) with header, body (terms highlights, full terms link, agreement checkbox), and footer.
*   **Form:** Contains a hidden HTML form (`#pitch-create-form`) targeting `route('projects.pitches.store')` via POST. Includes `@csrf` and `$project->id`.
*   **Agreement:** Requires user to check an 'Agree to Terms' checkbox (`#agree_terms`).
*   **JavaScript:** Includes inline JS defining global functions (`openPitchTermsModal`, `closePitchTermsModal`, `submitPitchForm`) for modal control and form submission. `submitPitchForm` validates checkbox before submitting.
*   **Styling:** Uses Tailwind CSS.

**Interaction:** Provides the UI flow for agreeing to terms before creating a pitch. Uses inline JavaScript for modal display and conditional form submission. Submits directly to a controller route upon confirmation.

*(Further view analysis pending...)*

### User Link Component (`user-link.blade.php`)

**File:** `resources/views/components/user-link.blade.php`

**Purpose:** Renders a user's name, providing a link to their public profile if they have a username.

**Usage:** `<x-user-link :user="$userObject" />`

**Input Props:**
*   `$user`: The `User` model instance.

**Key Features:**
*   Conditionally renders an anchor tag linking to `route('profile.username', ['username' => $user->username])` if `$user->username` is set.
*   Displays the `$user->name` as the link text or plain text.
*   Basic Tailwind link styling.

**Interaction:** Displays user name, conditionally linking to their profile.

*(Further view analysis pending...)*

### Pitch Status Banner Component (`pitch-status.blade.php`)

**File:** `resources/views/components/pitch-status.blade.php`

**Purpose:** Renders a prominent banner displaying a formatted pitch status with dynamic background and text colors.

**Usage:** Likely used via `@include('components.pitch-status', ['status' => $statusString, 'bgColor' => $tailwindBgClass, 'textColor' => $tailwindTextClass])`

**Input Props (Implicit):**
*   `$status`: The pitch status string (e.g., 'ready_for_review').
*   `$bgColor`: A Tailwind background color class (e.g., 'bg-blue-100').
*   `$textColor`: A Tailwind text color class (e.g., 'text-blue-800').

**Key Features:**
*   Formats the `$status` string using `ucwords(str_replace('_', ' ', $status))` for display.
*   Dynamically applies background and text color classes passed via `$bgColor` and `$textColor` using `@apply`.
*   Styled with Tailwind CSS.

**Interaction:** Purely a display component for showing status in a styled banner.

*(Further view analysis pending...)*

### Project Status Button Component (`project-status-button.blade.php`)

**File:** `resources/views/components/project-status-button.blade.php`

**Purpose:** Renders a styled badge or indicator for a project's status, including a relevant icon.

**Usage:** Likely used via `@include('components.project-status-button', ['status' => $project->status, 'type' => 'inline'])` or within Blade component context.

**Input Props (Implicit):**
*   `$status`: The project status string ('unpublished', 'open', 'review', 'completed', 'closed').
*   `$type`: Controls styling variations (e.g., 'top-right' for specific positioning/rounding, defaults to 'inline' styling).

**Key Features:**
*   **Status Mapping:** Uses a PHP `@switch` statement to map project `$status` strings to specific Tailwind CSS classes (`$colorClass`) and Font Awesome icon classes (`$iconClass`).
*   **Custom Colors:** Uses custom color names (e.g., `bg-statusOpen`, `shadow-statusOpen`) presumably defined in Tailwind config or CSS.
*   **Conditional Styling:** Applies different `border-radius` styles based on the `$type` variable.
*   **Display:** Renders a `<span>` containing the icon and the raw `$status` string.
*   **Styling:** Uses Tailwind CSS and Font Awesome.

**Interaction:** Purely a display component for project status indication.

*(Further view analysis pending...)*

### Invoice List View (`invoices.blade.php`)

**File:** `resources/views/billing/invoices.blade.php`

**Purpose:** Displays a paginated list of all the user's invoices.

**Layout:** Uses `x-app-layout` with a header slot ("Billing History") and a back link.

**Data:** Expects an `$invoices` collection (likely paginated Cashier Invoice objects). Uses `session`.

**Structure:**
*   Displays session messages.
*   Shows an empty state if no invoices exist.
*   Displays invoices in a table: Number, Date, Amount, Status (Paid/Unpaid), Type (Pitch Payment/Standard), Actions (View/Download).
*   "Type" column checks metadata for `pitch_id` and links to the project if applicable.
*   Provides "View" and "Download" links per invoice.

**Interaction:** Displays invoice list. Provides links to view/download invoices and navigate to related projects. Assumes pagination if data is paginated.

*(Further view analysis pending...)*

### Billing Diagnostic View (`diagnostic.blade.php`)

**File:** `resources/views/billing/diagnostic.blade.php`

**Purpose:** Developer/admin tool to diagnose invoice synchronization issues by comparing raw Stripe invoice data with Cashier's local count.

**Layout:** Uses `x-app-layout` with a header slot ("Invoice Diagnostic") and a back link.

**Data:** Expects `$stripeId`, `$cashierInvoiceCount`, `$rawInvoiceData` (array of raw Stripe invoices).

**Structure:**
*   Displays Stripe Customer ID, Cashier invoice count, raw Stripe invoice count.
*   Shows a table of raw invoice data fetched directly from Stripe.
*   Provides a list of debugging steps, including suggesting an Artisan command (`stripe:sync-invoices --all`).
*   Includes a "Return to Billing" link.

**Interaction:** Static display of diagnostic information and debugging steps.

*(Further view analysis pending...)*

### Payment Methods Management View (`payment-methods.blade.php`)

**File:** `resources/views/billing/payment-methods.blade.php`

**Purpose:** Allows users to view all saved payment methods, set a default, add new ones, and remove existing ones.

**Layout:** Uses `x-app-layout` with a header slot ("Payment Methods").

**Data:** Expects `$paymentMethods` (collection), `$defaultPaymentMethod`, `$intent` (Stripe SetupIntent). Uses `session`.

**Structure:**
*   Displays session messages.
*   "Add New Card" button toggles a hidden Stripe Elements form.
*   Lists existing payment methods with icon, brand, last 4, expiry.
*   Highlights the default method.
*   Provides "Make Default" button (POST form) for non-default methods.
*   Provides "Remove" button (DELETE form with JS confirm) for all methods.
*   Shows empty state message.
*   Footer links to Billing overview and Stripe Portal.

**JavaScript (`@push('scripts')`):**
*   Includes Stripe.js.
*   Initializes Stripe, mounts Card Element, handles errors.
*   Handles add card form submission using `confirmCardSetup` with SetupIntent, then submits to backend.
*   Toggles add card form visibility.

**Interaction:** Manages multiple payment methods, integrates Stripe Elements for adding cards, uses forms for setting default/removing.

*(Further view analysis pending...)*

### Checkout View (`checkout.blade.php`)

**File:** `resources/views/billing/checkout.blade.php`

**Purpose:** Provides a dedicated page for handling payments, likely for subscriptions or other purchases initiated via Cashier's `checkout`. Focuses solely on collecting payment details.

**Layout:** Uses `x-app-layout` with a header slot ("Checkout").

**Data:** Expects `$intent` (Stripe SetupIntent) and potentially `$priceId`. Uses `session`.

**Structure:**
*   Displays session messages.
*   Contains a single form POSTing to `billing.payment.process`.
*   Includes hidden `price_id` (if provided).
*   Includes Stripe Card Element (`#card-element`) and error display (`#card-errors`).
*   Submit button holds SetupIntent secret.
*   Provides a "Back to Billing" link.

**JavaScript (`@push('scripts')`):**
*   Includes Stripe.js.
*   Initializes Stripe, mounts Card Element, handles errors.
*   Handles form submission using `confirmCardSetup` with SetupIntent, then submits payment method ID and price ID to backend.

**Interaction:** Streamlined checkout page using Stripe Elements and SetupIntents to capture card details for payment processing.

*(Further view analysis pending...)*

### Detailed Invoice View (`invoice-details.blade.php` - Potentially Redundant)

**File:** `resources/views/billing/invoice-details.blade.php`

**Note:** This is a **full page view**, not the component `components/invoice-details.blade.php`. It appears redundant and potentially unused, as `invoice-show.blade.php` uses the component.

**Purpose:** Displays detailed invoice information, including line items, summary, payment method, and billing address.

**Layout:** Uses `x-app-layout` with header including back/download links.

**Data:** Expects `$invoice` (Cashier Invoice object). Uses `session`.

**Structure:**
*   Displays session messages.
*   Shows Invoice Header (Number, Date, Status).
*   Shows Summary (Subtotal, Tax, Discount, Total).
*   Shows Line Items table.
*   **Contains logic within the view** to fetch related Stripe PaymentMethod (for paid invoices) and Customer objects using the Stripe SDK.
*   Displays Payment Method details (Card Brand, Last 4, Expiry).
*   Displays Billing Details (Name, Email, Address).

**Interaction:** Static display of detailed invoice info. Includes logic within the view to fetch related Stripe data.

**Potential Issues:**
*   Likely redundant due to `invoice-show.blade.php` using the `x-invoice-details` component.
*   Contains business logic (Stripe SDK calls) within the view, violating separation of concerns.

*(Further view analysis pending...)*

## Email Views (`resources/views/emails/`)

### Email Test Form View (`test-form.blade.php`)

**File:** `resources/views/emails/test-form.blade.php`

**Purpose:** Provides a simple form for sending a test email (likely corresponds to `EmailController::showTestForm`).

**Layout:** Uses `x-app-layout` with a "Test Email" header.

**Data:** Uses `session` and `@error` directive.

**Structure:**
*   Displays session success/error messages.
*   Simple form POSTing to `email.test.send`.
*   Includes `@csrf`.
*   Input field for recipient email (`name="email"`) with validation error display.
*   "Send Test Email" submit button.

**Interaction:** User enters email and submits form to trigger sending of a predefined test email via the backend.

*(Further view analysis pending...)*

### Test Email Template (`test.blade.php`)

**File:** `resources/views/emails/test.blade.php`

**Purpose:** Defines the HTML structure and content for the test email sent via the `TestMail` Mailable.

**Layout:** Standalone HTML file with basic inline CSS. Does not use standard Laravel email components/markdown.

**Data:** Uses `date('Y')`.

**Structure:**
*   Simple centered container.
*   Static heading and text confirming email configuration (mentioning SES) is working.
*   Basic footer with copyright.

**Interaction:** Static email content.

*(Further view analysis pending...)*

### Jetstream Team Invitation Email (`team-invitation.blade.php`)

**File:** `resources/views/emails/team-invitation.blade.php`

**Purpose:** Standard Jetstream email template sent when a user is invited to a team.

**Layout:** Uses Laravel Markdown Mail components (`mail::message`, `mail::button`).

**Data:** Expects `$invitation` (Jetstream object) and `$acceptUrl`. Uses Fortify features check.

**Structure:**
*   States which team the user is invited to.
*   Conditionally includes a "Create Account" button if registration is enabled.
*   Includes an "Accept Invitation" button linking to `$acceptUrl`.
*   Includes discard instructions.

**Interaction:** Provides buttons to create account/accept invitation.

*(Further view analysis pending...)*

### Payment Receipt Email (`payment/receipt.blade.php`)

**File:** `resources/views/emails/payment/receipt.blade.php`

**Purpose:** Email template sent to both project owner and pitch creator upon successful payment or project completion (for free projects).

**Layout:** Standalone HTML file with basic inline CSS.

**Data:** Expects `$isFreeProject`, `$recipientName`, `$recipientType`, `$project`, `$pitch`, `$paymentDate`, `$invoiceId`, `$amount`.

**Structure:**
*   Mixpitch logo and title.
*   Greeting and text confirming payment/completion, tailored to recipient.
*   Summary box: Project Name, Pitch ID, Date, Invoice ID (if applicable), Amount/"Free Project".
*   Concluding message tailored to recipient.
*   Button linking to the web receipt view (`projects.pitches.payment.receipt`).
*   Standard footer.

**Interaction:** Confirmation email with link to web receipt.

*(Further view analysis pending...)*

## Filament Views (`resources/views/filament/`)

**Purpose:** Contains Blade views used by the Filament Admin Panel.

**Structure:** Organized into subdirectories like `pages`, `resources`, `widgets`, etc., mirroring Filament concepts.

**Analysis:** Views generally follow standard Filament patterns:
*   Use Filament layout components (e.g., `<x-filament::page>`).
*   Render widgets using `<x-filament-widgets::widgets />` based on configurations in PHP classes.
*   Embed Livewire components for specific functionality (e.g., `<livewire:email-test-form />` in `test-email-page.blade.php`).
*   Minimal custom Blade logic; complexity resides in corresponding `app/Filament/` PHP classes and embedded Livewire components.

**(Skipping detailed analysis of individual files as they follow standard Filament structure).**

*(Further view analysis pending...)*

## Vendor Views (`resources/views/vendor/`)

This directory is **empty**. No third-party package views have been published for customization.

*(Further view analysis pending...)*

## User Profile Views (`resources/views/user-profile/` & `resources/views/profile/`)

### Public Profile View (`user-profile/show.blade.php`)

**File:** `resources/views/user-profile/show.blade.php`

**Purpose:** Displays the public profile page for a given user.

**Layout:** Uses `x-app-layout`.

**Data:** Expects `$user`, `$canEdit` (boolean), `$projects` (collection), `$completedPitches` (collection).

**Structure:**
*   **Profile Header:** Avatar, Name, Username, Headline, Location, Website. Conditional "Edit Profile" button. Social media links, Tipjar link.
*   **Main Content Grid (2 columns):**
    *   **Left:** About (Bio, conditional Tipjar button), Skills/Equipment/Specialties (displayed as badges).
    *   **Right:** User's public Projects grid, Completed Pitches list.

**Interaction:** Displays profile info, projects, completed work. Includes external links (social, website, tipjar) and edit link for owner.

*(Further view analysis pending...)*

### Profile Edit View (Livewire) (`user-profile/edit-livewire.blade.php`)

**File:** `resources/views/user-profile/edit-livewire.blade.php`

**Purpose:** Container view that loads the `UserProfileEdit` Livewire component for editing the authenticated user's profile.

**Layout:** Uses `x-app-layout`.

**Data:** None passed directly; Livewire component fetches authenticated user.

**Structure:** Includes `<livewire:user-profile-edit />`.

**Interaction:** All logic handled by the embedded `UserProfileEdit` Livewire component.

*(Further view analysis pending...)*

### Profile Edit View (Standard Form - Potentially Redundant) (`user-profile/edit.blade.php`)

**File:** `resources/views/user-profile/edit.blade.php`

**Note:** This appears to be a standard HTML form implementation for profile editing, likely redundant due to the Livewire version (`user-profile/edit-livewire.blade.php` loading `UserProfileEdit`).

**Purpose:** Allows authenticated user to edit profile details via a standard form.

**Layout:** Uses `x-app-layout` with "Edit Profile" header and conditional public profile link.

**Data:** Expects `$user`. Uses `session`, `@error`, `old()`.

**Structure:**
*   Displays session messages and username setup prompt.
*   Standard HTML form POSTing to `profile.update` (PUT method).
*   Inputs for Username, Bio, Website, Location.
*   Inputs for Social Links (Twitter, Instagram, SoundCloud, Spotify, YouTube) using array format (`social_links[platform]`). Attempts to use `$this->getSocialUsername()` for pre-filling, suggesting it might have been intended for a Livewire component or requires a helper method.
*   "Save" button.

**Interaction:** Standard HTML form submission to `UserProfileController::update`.

**Redundancy:** Likely redundant/unused in favor of the Livewire implementation.

*(Further view analysis pending...)*

### Jetstream Profile Views (`resources/views/profile/`)

**Purpose:** Contains standard Jetstream views for user profile management (update info, password, 2FA, browser sessions, delete account).

**Structure:** The main view (`show.blade.php`) uses `x-app-layout` and conditionally includes various `@livewire('profile.*')` components based on enabled Jetstream/Fortify features. Other files in the directory are the Blade views for these embedded Livewire components.

**Interaction:** All logic is handled by the embedded Jetstream Livewire components.

**(Skipping detailed analysis of individual files as they are standard Jetstream components).**

*(Further view analysis pending...)*

## Pitch Files Views (`resources/views/pitch-files/`)

### Pitch File Show View (`show.blade.php`)

**File:** `resources/views/pitch-files/show.blade.php`

**Purpose:** Container view to display a single pitch file using the `PitchFilePlayer` Livewire component.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$file` (PitchFile model).

**Structure:**
*   Includes `<livewire:pitch-file-player :file="$file" />`.
*   Pushes basic CSS for waveform loading/timeline.
*   Pushes Wavesurfer.js library.

**Interaction:** All logic handled by the embedded `PitchFilePlayer` Livewire component.

*(Further view analysis pending...)*

## Authentication Views (`resources/views/auth/`)

**Purpose:** Contains standard Laravel Fortify views for handling authentication flows (login, registration, password reset, two-factor auth, etc.).

**Structure:** Views use `x-guest-layout` and standard Jetstream/Fortify Blade components (e.g., `x-authentication-card`). They correspond to Fortify's backend routes and actions.

**Interaction:** Standard HTML forms submitting to Fortify controllers/actions.

**(Skipping detailed analysis of individual files as they are standard Fortify views).**

*(Further view analysis pending...)*

## API Token Views (`resources/views/api/`)

**Purpose:** Contains standard Jetstream views for API token management.

**Structure:** The main view (`index.blade.php`) uses `x-app-layout` and includes the `@livewire('api.api-token-manager')` component. The other file (`api-token-manager.blade.php`) is the view for that Livewire component.

**Interaction:** All logic handled by the embedded Jetstream Livewire component.

**(Skipping detailed analysis as they are standard Jetstream components).**

*(Further view analysis pending...)*

## Projects Views (`resources/views/projects/`)

### Public Project Show View (`project.blade.php`)

**File:** `resources/views/projects/project.blade.php`

**Purpose:** Displays the public detail page for a single project.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project` (with relations), `$canPitch` (boolean), `$userPitch` (Pitch model or null). Uses `Auth`.

**Structure:**
*   **Header Card:** Project image (Alpine lightbox), preview track audio player (Livewire), Project Name, Artist, Owner (`x-user-link`), Status (`x-project-status-button`), Type, Deadline. Conditional buttons (Manage Project / Start Pitch / View Pitch).
*   **Details Section:** Collaboration badges, Budget, Description, Notes.

**Interaction:** Displays project details. Conditional actions for owner (Manage) or others (Start/View Pitch). Pitch initiation uses JS modal (`openPitchTermsModal()`). Includes audio player and image lightbox.

*(Further view analysis pending...)*

### Project Edit View (Standard Form - Potentially Redundant) (`edit.blade.php`)

**File:** `resources/views/projects/edit.blade.php`

**Note:** Likely redundant due to the `ManageProject` Livewire component handling project editing and file management.

**Purpose:** Allows editing project details and managing files via standard HTML forms.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project` (with relations). Uses `old()`.

**Structure:**
*   **Header:** Displays project image (clickable for upload via JS), inputs for Name, Description, Genre, Status.
*   **Form:** Standard HTML form POSTing to `projects.update` (PUT method), `enctype="multipart/form-data"`.
*   **File Management:** Lists existing files with Delete buttons (individual DELETE forms triggered by JS). Links to a separate `projects.createStep2` route for uploads.

**JavaScript:** Includes `loadFile` for image preview and event listener for delete buttons.

**Interaction:** Standard form submission for edits. File deletion via separate forms triggered by JS. Links to potentially deprecated upload step.

**Redundancy:** Likely redundant/unused in favor of the `ManageProject` Livewire implementation.

### Project Index View (`index.blade.php` - Livewire Wrapper)

**File:** `resources/views/projects/index.blade.php`

**Purpose:** Container view for the public project listing.

**Layout:** Extends `components.layouts.app`.

**Structure:** Includes `<livewire:projects-component />`. Assumes the listing logic is handled by this Livewire component.

### Project Upload (Step 2) View (`create_step2.blade.php` - Likely Deprecated)

**File:** `resources/views/projects/create_step2.blade.php`

**Purpose:** Provides a Dropzone.js interface for uploading files as part of a multi-step project creation/edit process.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project`.

**Structure:** Includes Dropzone CSS/JS. Contains Dropzone form POSTing to `projects.storeStep2`. Includes Back/Finish links.

**JavaScript:** Initializes Dropzone.

**Redundancy:** Uses Dropzone and separate upload step, contrasting with integrated S3 uploads in `ManageProject`. Highly likely deprecated.

### Upload Project View (`upload-project.blade.php` - TrackController related)

**File:** `resources/views/projects/upload-project.blade.php`

**Purpose:** Container view, likely related to the `TrackController` functionality.

**Layout:** Extends `components.layouts.app`.

**Structure:** Includes `<livewire:upload-project-component />`. Logic is assumed to be in this Livewire component.

*(Further view analysis pending...)*

## Components (`resources/views/components/`)

### Invoice Details Component (`invoice-details.blade.php`)

**File:** `resources/views/components/invoice-details.blade.php`

**Purpose:** Renders a detailed view of a single invoice, likely using data fetched from Stripe.

**Usage:** Expected to be used like `<x-invoice-details :invoice="$invoiceObject" :view-all-url="route(...)" />`

**Input Props:**
*   `$invoice`: An object containing invoice data (ID/number, status, date, total, description, line items). Structure suggests compatibility with Stripe API responses or formatted objects from `InvoiceService`.
*   `$viewAllUrl` (optional): A URL string for a 'View all invoices' link.

**Key Features:**
*   Displays invoice number, paid/unpaid status badge, date, and total amount.
*   Lists line items from `$invoice->lines->data` or shows a single summary line.
*   Provides a 'Download PDF' link to `route('billing.invoice.download', $invoice->id)`.
*   Optionally provides a 'View all invoices' link.
*   Styled with Tailwind CSS.

**Interaction:** Primarily a display component formatting invoice data. Provides navigation links for downloading and viewing other invoices.

*(Further view analysis pending...)*

### Update Pitch Status Buttons Component (`update-pitch-status.blade.php`)

**File:** `resources/views/components/update-pitch-status.blade.php`

**Purpose:** Renders action buttons for changing a pitch's status based on its current state.
**Note: This component appears highly problematic and likely redundant/deprecated.** Its logic seems to belong within the `UpdatePitchStatus` Livewire component's view (`resources/views/livewire/pitch/component/update-pitch-status.blade.php`). This Blade component bypasses the Livewire component's actions and service layer calls in many cases.

**Usage:** Likely intended usage `<x-update-pitch-status :pitch="$pitch" />`

**Input Props:**
*   `$pitch`: The `Pitch` model.

**Key Features & Issues:**
*   **Conditional Logic:** Uses extensive `@if/@elseif` based on `$pitch->status` to determine which actions/buttons to show.
*   **Direct Form Submissions (Issue):** For several statuses (Pending, In Progress, Approved, Denied, Revisions Requested, Completed), it renders HTML forms that POST directly to controller routes (`projects.pitches.change-status`, `projects.pitches.return-to-approved`). This bypasses the `UpdatePitchStatus` Livewire component and the `PitchWorkflowService`, potentially leading to inconsistent state management and authorization issues.
*   **JavaScript Modal Triggers (Partial Alignment):** For the `READY_FOR_REVIEW` status, it renders buttons that trigger JavaScript functions (`openApproveModal`, etc.) presumably defined elsewhere to interact with modals (`<x-pitch-action-modals />`). This aligns partially with the Livewire component's modal interaction but still passes controller routes directly to JS instead of using Livewire actions.
*   **Includes Modals:** Includes `<x-pitch-action-modals />`.

**Interaction:** Renders conditional buttons/forms. Interacts via direct form POSTs or JavaScript calls, largely bypassing the corresponding Livewire component. Includes a modal component.

**Recommendation:** This component should be reviewed and likely removed. Its logic needs to be consolidated within the `UpdatePitchStatus` Livewire component and its associated view to ensure consistent use of the `PitchWorkflowService` and proper state management.

*(Further view analysis pending...)*

### Pitch Action Modals Component (`pitch-action-modals.blade.php`)

**File:** `resources/views/components/pitch-action-modals.blade.php`

**Purpose:** Defines the HTML structure for modals used for Approve, Deny, and Request Revisions actions on pitches.

**Usage:** Included by other components, e.g., `<x-pitch-action-modals />` (as seen in `update-pitch-status.blade.php`).

**Input Props:** None.

**Key Features:**
*   **Structure:** Contains three distinct modal sections (`#approveModal`, `#denyModal`, `#revisionsModal`), initially hidden.
*   **Forms:** Each modal contains a basic, hidden HTML form (`#approveForm`, etc.) with only `@csrf`. JavaScript is expected to set the `action` URL and potentially add data before submission.
*   **Inputs:** Provides textareas for denial reason (`#denyReason`) and revision requests (`#revisionsRequested`).
*   **Buttons:** Includes Cancel buttons (`onclick="closeModal(...)"`) and confirmation buttons (`#approveSubmitBtn`, etc.) presumably handled by JavaScript.
*   **JavaScript Dependency:** Includes `<script src="{{ asset('js/pitch-modals.js') }}"></script>`, indicating all modal interaction logic (opening, closing, submitting the hidden forms) resides in this external JS file.
*   **Styling:** Uses Tailwind CSS.

**Interaction:** Provides the static modal HTML. Relies entirely on `pitch-modals.js` (and functions like `openApproveModal`, `closeModal`) for display logic and form submission, targeting controller routes directly. This approach is less integrated with Livewire/Alpine compared to using Livewire components for modal state and actions.

**Note:** The reliance on external JS to manipulate and submit hidden forms directly to controller routes is part of the potentially problematic pattern identified in `components/update-pitch-status.blade.php`.

*(Further view analysis pending...)*

### Pitch Terms Modal Component (`pitch-terms-modal.blade.php`)

**File:** `resources/views/components/pitch-terms-modal.blade.php`

**Purpose:** Displays a modal with terms highlights that must be agreed to before initiating a pitch creation request.

**Usage:** Likely included on project detail pages, e.g., `<x-pitch-terms-modal :project="$project" />`. Triggered via JavaScript (`openPitchTermsModal()`).

**Input Props:**
*   `$project`: The `Project` model the user wants to pitch for.

**Key Features:**
*   **Structure:** Defines a modal (`#pitch-terms-modal`) with header, body (terms highlights, full terms link, agreement checkbox), and footer.
*   **Form:** Contains a hidden HTML form (`#pitch-create-form`) targeting `route('projects.pitches.store')` via POST. Includes `@csrf` and `$project->id`.
*   **Agreement:** Requires user to check an 'Agree to Terms' checkbox (`#agree_terms`).
*   **JavaScript:** Includes inline JS defining global functions (`openPitchTermsModal`, `closePitchTermsModal`, `submitPitchForm`) for modal control and form submission. `submitPitchForm` validates checkbox before submitting.
*   **Styling:** Uses Tailwind CSS.

**Interaction:** Provides the UI flow for agreeing to terms before creating a pitch. Uses inline JavaScript for modal display and conditional form submission. Submits directly to a controller route upon confirmation.

*(Further view analysis pending...)*

### User Link Component (`user-link.blade.php`)

**File:** `resources/views/components/user-link.blade.php`

**Purpose:** Renders a user's name, providing a link to their public profile if they have a username.

**Usage:** `<x-user-link :user="$userObject" />`

**Input Props:**
*   `$user`: The `User` model instance.

**Key Features:**
*   Conditionally renders an anchor tag linking to `route('profile.username', ['username' => $user->username])` if `$user->username` is set.
*   Displays the `$user->name` as the link text or plain text.
*   Basic Tailwind link styling.

**Interaction:** Displays user name, conditionally linking to their profile.

*(Further view analysis pending...)*

### Pitch Status Banner Component (`pitch-status.blade.php`)

**File:** `resources/views/components/pitch-status.blade.php`

**Purpose:** Renders a prominent banner displaying a formatted pitch status with dynamic background and text colors.

**Usage:** Likely used via `@include('components.pitch-status', ['status' => $statusString, 'bgColor' => $tailwindBgClass, 'textColor' => $tailwindTextClass])`

**Input Props (Implicit):**
*   `$status`: The pitch status string (e.g., 'ready_for_review').
*   `$bgColor`: A Tailwind background color class (e.g., 'bg-blue-100').
*   `$textColor`: A Tailwind text color class (e.g., 'text-blue-800').

**Key Features:**
*   Formats the `$status` string using `ucwords(str_replace('_', ' ', $status))` for display.
*   Dynamically applies background and text color classes passed via `$bgColor` and `$textColor` using `@apply`.
*   Styled with Tailwind CSS.

**Interaction:** Purely a display component for showing status in a styled banner.

*(Further view analysis pending...)*

### Project Status Button Component (`project-status-button.blade.php`)

**File:** `resources/views/components/project-status-button.blade.php`

**Purpose:** Renders a styled badge or indicator for a project's status, including a relevant icon.

**Usage:** Likely used via `@include('components.project-status-button', ['status' => $project->status, 'type' => 'inline'])` or within Blade component context.

**Input Props (Implicit):**
*   `$status`: The project status string ('unpublished', 'open', 'review', 'completed', 'closed').
*   `$type`: Controls styling variations (e.g., 'top-right' for specific positioning/rounding, defaults to 'inline' styling).

**Key Features:**
*   **Status Mapping:** Uses a PHP `@switch` statement to map project `$status` strings to specific Tailwind CSS classes (`$colorClass`) and Font Awesome icon classes (`$iconClass`).
*   **Custom Colors:** Uses custom color names (e.g., `bg-statusOpen`, `shadow-statusOpen`) presumably defined in Tailwind config or CSS.
*   **Conditional Styling:** Applies different `border-radius` styles based on the `$type` variable.
*   **Display:** Renders a `<span>` containing the icon and the raw `$status` string.
*   **Styling:** Uses Tailwind CSS and Font Awesome.

**Interaction:** Purely a display component for project status indication.

*(Further view analysis pending...)*

### Invoice List View (`invoices.blade.php`)

**File:** `resources/views/billing/invoices.blade.php`

**Purpose:** Displays a paginated list of all the user's invoices.

**Layout:** Uses `x-app-layout` with a header slot ("Billing History") and a back link.

**Data:** Expects an `$invoices` collection (likely paginated Cashier Invoice objects). Uses `session`.

**Structure:**
*   Displays session messages.
*   Shows an empty state if no invoices exist.
*   Displays invoices in a table: Number, Date, Amount, Status (Paid/Unpaid), Type (Pitch Payment/Standard), Actions (View/Download).
*   "Type" column checks metadata for `pitch_id` and links to the project if applicable.
*   Provides "View" and "Download" links per invoice.

**Interaction:** Displays invoice list. Provides links to view/download invoices and navigate to related projects. Assumes pagination if data is paginated.

*(Further view analysis pending...)*

### Billing Diagnostic View (`diagnostic.blade.php`)

**File:** `resources/views/billing/diagnostic.blade.php`

**Purpose:** Developer/admin tool to diagnose invoice synchronization issues by comparing raw Stripe invoice data with Cashier's local count.

**Layout:** Uses `x-app-layout` with a header slot ("Invoice Diagnostic") and a back link.

**Data:** Expects `$stripeId`, `$cashierInvoiceCount`, `$rawInvoiceData` (array of raw Stripe invoices).

**Structure:**
*   Displays Stripe Customer ID, Cashier invoice count, raw Stripe invoice count.
*   Shows a table of raw invoice data fetched directly from Stripe.
*   Provides a list of debugging steps, including suggesting an Artisan command (`stripe:sync-invoices --all`).
*   Includes a "Return to Billing" link.

**Interaction:** Static display of diagnostic information and debugging steps.

*(Further view analysis pending...)*

### Payment Methods Management View (`payment-methods.blade.php`)

**File:** `resources/views/billing/payment-methods.blade.php`

**Purpose:** Allows users to view all saved payment methods, set a default, add new ones, and remove existing ones.

**Layout:** Uses `x-app-layout` with a header slot ("Payment Methods").

**Data:** Expects `$paymentMethods` (collection), `$defaultPaymentMethod`, `$intent` (Stripe SetupIntent). Uses `session`.

**Structure:**
*   Displays session messages.
*   "Add New Card" button toggles a hidden Stripe Elements form.
*   Lists existing payment methods with icon, brand, last 4, expiry.
*   Highlights the default method.
*   Provides "Make Default" button (POST form) for non-default methods.
*   Provides "Remove" button (DELETE form with JS confirm) for all methods.
*   Shows empty state message.
*   Footer links to Billing overview and Stripe Portal.

**JavaScript (`@push('scripts')`):**
*   Includes Stripe.js.
*   Initializes Stripe, mounts Card Element, handles errors.
*   Handles add card form submission using `confirmCardSetup` with SetupIntent, then submits to backend.
*   Toggles add card form visibility.

**Interaction:** Manages multiple payment methods, integrates Stripe Elements for adding cards, uses forms for setting default/removing.

*(Further view analysis pending...)*

### Checkout View (`checkout.blade.php`)

**File:** `resources/views/billing/checkout.blade.php`

**Purpose:** Provides a dedicated page for handling payments, likely for subscriptions or other purchases initiated via Cashier's `checkout`. Focuses solely on collecting payment details.

**Layout:** Uses `x-app-layout` with a header slot ("Checkout").

**Data:** Expects `$intent` (Stripe SetupIntent) and potentially `$priceId`. Uses `session`.

**Structure:**
*   Displays session messages.
*   Contains a single form POSTing to `billing.payment.process`.
*   Includes hidden `price_id` (if provided).
*   Includes Stripe Card Element (`#card-element`) and error display (`#card-errors`).
*   Submit button holds SetupIntent secret.
*   Provides a "Back to Billing" link.

**JavaScript (`@push('scripts')`):**
*   Includes Stripe.js.
*   Initializes Stripe, mounts Card Element, handles errors.
*   Handles form submission using `confirmCardSetup` with SetupIntent, then submits payment method ID and price ID to backend.

**Interaction:** Streamlined checkout page using Stripe Elements and SetupIntents to capture card details for payment processing.

*(Further view analysis pending...)*

### Detailed Invoice View (`invoice-details.blade.php` - Potentially Redundant)

**File:** `resources/views/billing/invoice-details.blade.php`

**Note:** This is a **full page view**, not the component `components/invoice-details.blade.php`. It appears redundant and potentially unused, as `invoice-show.blade.php` uses the component.

**Purpose:** Displays detailed invoice information, including line items, summary, payment method, and billing address.

**Layout:** Uses `x-app-layout` with header including back/download links.

**Data:** Expects `$invoice` (Cashier Invoice object). Uses `session`.

**Structure:**
*   Displays session messages.
*   Shows Invoice Header (Number, Date, Status).
*   Shows Summary (Subtotal, Tax, Discount, Total).
*   Shows Line Items table.
*   **Contains logic within the view** to fetch related Stripe PaymentMethod (for paid invoices) and Customer objects using the Stripe SDK.
*   Displays Payment Method details (Card Brand, Last 4, Expiry).
*   Displays Billing Details (Name, Email, Address).

**Interaction:** Static display of detailed invoice info. Includes logic within the view to fetch related Stripe data.

**Potential Issues:**
*   Likely redundant due to `invoice-show.blade.php` using the `x-invoice-details` component.
*   Contains business logic (Stripe SDK calls) within the view, violating separation of concerns.

*(Further view analysis pending...)*

## Email Views (`resources/views/emails/`)

### Email Test Form View (`test-form.blade.php`)

**File:** `resources/views/emails/test-form.blade.php`

**Purpose:** Provides a simple form for sending a test email (likely corresponds to `EmailController::showTestForm`).

**Layout:** Uses `x-app-layout` with a "Test Email" header.

**Data:** Uses `session` and `@error` directive.

**Structure:**
*   Displays session success/error messages.
*   Simple form POSTing to `email.test.send`.
*   Includes `@csrf`.
*   Input field for recipient email (`name="email"`) with validation error display.
*   "Send Test Email" submit button.

**Interaction:** User enters email and submits form to trigger sending of a predefined test email via the backend.

*(Further view analysis pending...)*

### Test Email Template (`test.blade.php`)

**File:** `resources/views/emails/test.blade.php`

**Purpose:** Defines the HTML structure and content for the test email sent via the `TestMail` Mailable.

**Layout:** Standalone HTML file with basic inline CSS. Does not use standard Laravel email components/markdown.

**Data:** Uses `date('Y')`.

**Structure:**
*   Simple centered container.
*   Static heading and text confirming email configuration (mentioning SES) is working.
*   Basic footer with copyright.

**Interaction:** Static email content.

*(Further view analysis pending...)*

### Jetstream Team Invitation Email (`team-invitation.blade.php`)

**File:** `resources/views/emails/team-invitation.blade.php`

**Purpose:** Standard Jetstream email template sent when a user is invited to a team.

**Layout:** Uses Laravel Markdown Mail components (`mail::message`, `mail::button`).

**Data:** Expects `$invitation` (Jetstream object) and `$acceptUrl`. Uses Fortify features check.

**Structure:**
*   States which team the user is invited to.
*   Conditionally includes a "Create Account" button if registration is enabled.
*   Includes an "Accept Invitation" button linking to `$acceptUrl`.
*   Includes discard instructions.

**Interaction:** Provides buttons to create account/accept invitation.

*(Further view analysis pending...)*

### Payment Receipt Email (`payment/receipt.blade.php`)

**File:** `resources/views/emails/payment/receipt.blade.php`

**Purpose:** Email template sent to both project owner and pitch creator upon successful payment or project completion (for free projects).

**Layout:** Standalone HTML file with basic inline CSS.

**Data:** Expects `$isFreeProject`, `$recipientName`, `$recipientType`, `$project`, `$pitch`, `$paymentDate`, `$invoiceId`, `$amount`.

**Structure:**
*   Mixpitch logo and title.
*   Greeting and text confirming payment/completion, tailored to recipient.
*   Summary box: Project Name, Pitch ID, Date, Invoice ID (if applicable), Amount/"Free Project".
*   Concluding message tailored to recipient.
*   Button linking to the web receipt view (`projects.pitches.payment.receipt`).
*   Standard footer.

**Interaction:** Confirmation email with link to web receipt.

*(Further view analysis pending...)*

## Filament Views (`resources/views/filament/`)

**Purpose:** Contains Blade views used by the Filament Admin Panel.

**Structure:** Organized into subdirectories like `pages`, `resources`, `widgets`, etc., mirroring Filament concepts.

**Analysis:** Views generally follow standard Filament patterns:
*   Use Filament layout components (e.g., `<x-filament::page>`).
*   Render widgets using `<x-filament-widgets::widgets />` based on configurations in PHP classes.
*   Embed Livewire components for specific functionality (e.g., `<livewire:email-test-form />` in `test-email-page.blade.php`).
*   Minimal custom Blade logic; complexity resides in corresponding `app/Filament/` PHP classes and embedded Livewire components.

**(Skipping detailed analysis of individual files as they follow standard Filament structure).**

*(Further view analysis pending...)*

## Vendor Views (`resources/views/vendor/`)

This directory is **empty**. No third-party package views have been published for customization.

*(Further view analysis pending...)*

## User Profile Views (`resources/views/user-profile/` & `resources/views/profile/`)

### Public Profile View (`user-profile/show.blade.php`)

**File:** `resources/views/user-profile/show.blade.php`

**Purpose:** Displays the public profile page for a given user.

**Layout:** Uses `x-app-layout`.

**Data:** Expects `$user`, `$canEdit` (boolean), `$projects` (collection), `$completedPitches` (collection).

**Structure:**
*   **Profile Header:** Avatar, Name, Username, Headline, Location, Website. Conditional "Edit Profile" button. Social media links, Tipjar link.
*   **Main Content Grid (2 columns):**
    *   **Left:** About (Bio, conditional Tipjar button), Skills/Equipment/Specialties (displayed as badges).
    *   **Right:** User's public Projects grid, Completed Pitches list.

**Interaction:** Displays profile info, projects, completed work. Includes external links (social, website, tipjar) and edit link for owner.

*(Further view analysis pending...)*

### Profile Edit View (Livewire) (`user-profile/edit-livewire.blade.php`)

**File:** `resources/views/user-profile/edit-livewire.blade.php`

**Purpose:** Container view that loads the `UserProfileEdit` Livewire component for editing the authenticated user's profile.

**Layout:** Uses `x-app-layout`.

**Data:** None passed directly; Livewire component fetches authenticated user.

**Structure:** Includes `<livewire:user-profile-edit />`.

**Interaction:** All logic handled by the embedded `UserProfileEdit` Livewire component.

*(Further view analysis pending...)*

### Profile Edit View (Standard Form - Potentially Redundant) (`user-profile/edit.blade.php`)

**File:** `resources/views/user-profile/edit.blade.php`

**Note:** This appears to be a standard HTML form implementation for profile editing, likely redundant due to the Livewire version (`user-profile/edit-livewire.blade.php` loading `UserProfileEdit`).

**Purpose:** Allows authenticated user to edit profile details via a standard form.

**Layout:** Uses `x-app-layout` with "Edit Profile" header and conditional public profile link.

**Data:** Expects `$user`. Uses `session`, `@error`, `old()`.

**Structure:**
*   Displays session messages and username setup prompt.
*   Standard HTML form POSTing to `profile.update` (PUT method).
*   Inputs for Username, Bio, Website, Location.
*   Inputs for Social Links (Twitter, Instagram, SoundCloud, Spotify, YouTube) using array format (`social_links[platform]`). Attempts to use `$this->getSocialUsername()` for pre-filling, suggesting it might have been intended for a Livewire component or requires a helper method.
*   "Save" button.

**Interaction:** Standard HTML form submission to `UserProfileController::update`.

**Redundancy:** Likely redundant/unused in favor of the Livewire implementation.

*(Further view analysis pending...)*

### Jetstream Profile Views (`resources/views/profile/`)

**Purpose:** Contains standard Jetstream views for user profile management (update info, password, 2FA, browser sessions, delete account).

**Structure:** The main view (`show.blade.php`) uses `x-app-layout` and conditionally includes various `@livewire('profile.*')` components based on enabled Jetstream/Fortify features. Other files in the directory are the Blade views for these embedded Livewire components.

**Interaction:** All logic is handled by the embedded Jetstream Livewire components.

**(Skipping detailed analysis of individual files as they are standard Jetstream components).**

*(Further view analysis pending...)*

## Pitch Files Views (`resources/views/pitch-files/`)

### Pitch File Show View (`show.blade.php`)

**File:** `resources/views/pitch-files/show.blade.php`

**Purpose:** Container view to display a single pitch file using the `PitchFilePlayer` Livewire component.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$file` (PitchFile model).

**Structure:**
*   Includes `<livewire:pitch-file-player :file="$file" />`.
*   Pushes basic CSS for waveform loading/timeline.
*   Pushes Wavesurfer.js library.

**Interaction:** All logic handled by the embedded `PitchFilePlayer` Livewire component.

*(Further view analysis pending...)*

## Authentication Views (`resources/views/auth/`)

**Purpose:** Contains standard Laravel Fortify views for handling authentication flows (login, registration, password reset, two-factor auth, etc.).

**Structure:** Views use `x-guest-layout` and standard Jetstream/Fortify Blade components (e.g., `x-authentication-card`). They correspond to Fortify's backend routes and actions.

**Interaction:** Standard HTML forms submitting to Fortify controllers/actions.

**(Skipping detailed analysis of individual files as they are standard Fortify views).**

*(Further view analysis pending...)*

## API Token Views (`resources/views/api/`)

**Purpose:** Contains standard Jetstream views for API token management.

**Structure:** The main view (`index.blade.php`) uses `x-app-layout` and includes the `@livewire('api.api-token-manager')` component. The other file (`api-token-manager.blade.php`) is the view for that Livewire component.

**Interaction:** All logic handled by the embedded Jetstream Livewire component.

**(Skipping detailed analysis as they are standard Jetstream components).**

*(Further view analysis pending...)*

## Projects Views (`resources/views/projects/`)

### Public Project Show View (`project.blade.php`)

**File:** `resources/views/projects/project.blade.php`

**Purpose:** Displays the public detail page for a single project.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project` (with relations), `$canPitch` (boolean), `$userPitch` (Pitch model or null). Uses `Auth`.

**Structure:**
*   **Header Card:** Project image (Alpine lightbox), preview track audio player (Livewire), Project Name, Artist, Owner (`x-user-link`), Status (`x-project-status-button`), Type, Deadline. Conditional buttons (Manage Project / Start Pitch / View Pitch).
*   **Details Section:** Collaboration badges, Budget, Description, Notes.

**Interaction:** Displays project details. Conditional actions for owner (Manage) or others (Start/View Pitch). Pitch initiation uses JS modal (`openPitchTermsModal()`). Includes audio player and image lightbox.

*(Further view analysis pending...)*

### Project Edit View (Standard Form - Potentially Redundant) (`edit.blade.php`)

**File:** `resources/views/projects/edit.blade.php`

**Note:** Likely redundant due to the `ManageProject` Livewire component handling project editing and file management.

**Purpose:** Allows editing project details and managing files via standard HTML forms.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project` (with relations). Uses `old()`.

**Structure:**
*   **Header:** Displays project image (clickable for upload via JS), inputs for Name, Description, Genre, Status.
*   **Form:** Standard HTML form POSTing to `projects.update` (PUT method), `enctype="multipart/form-data"`.
*   **File Management:** Lists existing files with Delete buttons (individual DELETE forms triggered by JS). Links to a separate `projects.createStep2` route for uploads.

**JavaScript:** Includes `loadFile` for image preview and event listener for delete buttons.

**Interaction:** Standard form submission for edits. File deletion via separate forms triggered by JS. Links to potentially deprecated upload step.

**Redundancy:** Likely redundant/unused in favor of the `ManageProject` Livewire implementation.

### Project Index View (`index.blade.php` - Livewire Wrapper)

**File:** `resources/views/projects/index.blade.php`

**Purpose:** Container view for the public project listing.

**Layout:** Extends `components.layouts.app`.

**Structure:** Includes `<livewire:projects-component />`. Assumes the listing logic is handled by this Livewire component.

### Project Upload (Step 2) View (`create_step2.blade.php` - Likely Deprecated)

**File:** `resources/views/projects/create_step2.blade.php`

**Purpose:** Provides a Dropzone.js interface for uploading files as part of a multi-step project creation/edit process.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project`.

**Structure:** Includes Dropzone CSS/JS. Contains Dropzone form POSTing to `projects.storeStep2`. Includes Back/Finish links.

**JavaScript:** Initializes Dropzone.

**Redundancy:** Uses Dropzone and separate upload step, contrasting with integrated S3 uploads in `ManageProject`. Highly likely deprecated.

### Upload Project View (`upload-project.blade.php` - TrackController related)

**File:** `resources/views/projects/upload-project.blade.php`

**Purpose:** Container view, likely related to the `TrackController` functionality.

**Layout:** Extends `components.layouts.app`.

**Structure:** Includes `<livewire:upload-project-component />`. Logic is assumed to be in this Livewire component.

*(Further view analysis pending...)*

## Components (`resources/views/components/`)

### Invoice Details Component (`invoice-details.blade.php`)

**File:** `resources/views/components/invoice-details.blade.php`

**Purpose:** Renders a detailed view of a single invoice, likely using data fetched from Stripe.

**Usage:** Expected to be used like `<x-invoice-details :invoice="$invoiceObject" :view-all-url="route(...)" />`

**Input Props:**
*   `$invoice`: An object containing invoice data (ID/number, status, date, total, description, line items). Structure suggests compatibility with Stripe API responses or formatted objects from `InvoiceService`.
*   `$viewAllUrl` (optional): A URL string for a 'View all invoices' link.

**Key Features:**
*   Displays invoice number, paid/unpaid status badge, date, and total amount.
*   Lists line items from `$invoice->lines->data` or shows a single summary line.
*   Provides a 'Download PDF' link to `route('billing.invoice.download', $invoice->id)`.
*   Optionally provides a 'View all invoices' link.
*   Styled with Tailwind CSS.

**Interaction:** Primarily a display component formatting invoice data. Provides navigation links for downloading and viewing other invoices.

*(Further view analysis pending...)*

### Update Pitch Status Buttons Component (`update-pitch-status.blade.php`)

**File:** `resources/views/components/update-pitch-status.blade.php`

**Purpose:** Renders action buttons for changing a pitch's status based on its current state.
**Note: This component appears highly problematic and likely redundant/deprecated.** Its logic seems to belong within the `UpdatePitchStatus` Livewire component's view (`resources/views/livewire/pitch/component/update-pitch-status.blade.php`). This Blade component bypasses the Livewire component's actions and service layer calls in many cases.

**Usage:** Likely intended usage `<x-update-pitch-status :pitch="$pitch" />`

**Input Props:**
*   `$pitch`: The `Pitch` model.

**Key Features & Issues:**
*   **Conditional Logic:** Uses extensive `@if/@elseif` based on `$pitch->status` to determine which actions/buttons to show.
*   **Direct Form Submissions (Issue):** For several statuses (Pending, In Progress, Approved, Denied, Revisions Requested, Completed), it renders HTML forms that POST directly to controller routes (`projects.pitches.change-status`, `projects.pitches.return-to-approved`). This bypasses the `UpdatePitchStatus` Livewire component and the `PitchWorkflowService`, potentially leading to inconsistent state management and authorization issues.
*   **JavaScript Modal Triggers (Partial Alignment):** For the `READY_FOR_REVIEW` status, it renders buttons that trigger JavaScript functions (`openApproveModal`, etc.) presumably defined elsewhere to interact with modals (`<x-pitch-action-modals />`). This aligns partially with the Livewire component's modal interaction but still passes controller routes directly to JS instead of using Livewire actions.
*   **Includes Modals:** Includes `<x-pitch-action-modals />`.

**Interaction:** Renders conditional buttons/forms. Interacts via direct form POSTs or JavaScript calls, largely bypassing the corresponding Livewire component. Includes a modal component.

**Recommendation:** This component should be reviewed and likely removed. Its logic needs to be consolidated within the `UpdatePitchStatus` Livewire component and its associated view to ensure consistent use of the `PitchWorkflowService` and proper state management.

*(Further view analysis pending...)*

### Pitch Action Modals Component (`pitch-action-modals.blade.php`)

**File:** `resources/views/components/pitch-action-modals.blade.php`

**Purpose:** Defines the HTML structure for modals used for Approve, Deny, and Request Revisions actions on pitches.

**Usage:** Included by other components, e.g., `<x-pitch-action-modals />` (as seen in `update-pitch-status.blade.php`).

**Input Props:** None.

**Key Features:**
*   **Structure:** Contains three distinct modal sections (`#approveModal`, `#denyModal`, `#revisionsModal`), initially hidden.
*   **Forms:** Each modal contains a basic, hidden HTML form (`#approveForm`, etc.) with only `@csrf`. JavaScript is expected to set the `action` URL and potentially add data before submission.
*   **Inputs:** Provides textareas for denial reason (`#denyReason`) and revision requests (`#revisionsRequested`).
*   **Buttons:** Includes Cancel buttons (`onclick="closeModal(...)"`) and confirmation buttons (`#approveSubmitBtn`, etc.) presumably handled by JavaScript.
*   **JavaScript Dependency:** Includes `<script src="{{ asset('js/pitch-modals.js') }}"></script>`, indicating all modal interaction logic (opening, closing, submitting the hidden forms) resides in this external JS file.
*   **Styling:** Uses Tailwind CSS.

**Interaction:** Provides the static modal HTML. Relies entirely on `pitch-modals.js` (and functions like `openApproveModal`, `closeModal`) for display logic and form submission, targeting controller routes directly. This approach is less integrated with Livewire/Alpine compared to using Livewire components for modal state and actions.

**Note:** The reliance on external JS to manipulate and submit hidden forms directly to controller routes is part of the potentially problematic pattern identified in `components/update-pitch-status.blade.php`.

*(Further view analysis pending...)*

### Pitch Terms Modal Component (`pitch-terms-modal.blade.php`)

**File:** `resources/views/components/pitch-terms-modal.blade.php`

**Purpose:** Displays a modal with terms highlights that must be agreed to before initiating a pitch creation request.

**Usage:** Likely included on project detail pages, e.g., `<x-pitch-terms-modal :project="$project" />`. Triggered via JavaScript (`openPitchTermsModal()`).

**Input Props:**
*   `$project`: The `Project` model the user wants to pitch for.

**Key Features:**
*   **Structure:** Defines a modal (`#pitch-terms-modal`) with header, body (terms highlights, full terms link, agreement checkbox), and footer.
*   **Form:** Contains a hidden HTML form (`#pitch-create-form`) targeting `route('projects.pitches.store')` via POST. Includes `@csrf` and `$project->id`.
*   **Agreement:** Requires user to check an 'Agree to Terms' checkbox (`#agree_terms`).
*   **JavaScript:** Includes inline JS defining global functions (`openPitchTermsModal`, `closePitchTermsModal`, `submitPitchForm`) for modal control and form submission. `submitPitchForm` validates checkbox before submitting.
*   **Styling:** Uses Tailwind CSS.

**Interaction:** Provides the UI flow for agreeing to terms before creating a pitch. Uses inline JavaScript for modal display and conditional form submission. Submits directly to a controller route upon confirmation.

*(Further view analysis pending...)*

### User Link Component (`user-link.blade.php`)

**File:** `resources/views/components/user-link.blade.php`

**Purpose:** Renders a user's name, providing a link to their public profile if they have a username.

**Usage:** `<x-user-link :user="$userObject" />`

**Input Props:**
*   `$user`: The `User` model instance.

**Key Features:**
*   Conditionally renders an anchor tag linking to `route('profile.username', ['username' => $user->username])` if `$user->username` is set.
*   Displays the `$user->name` as the link text or plain text.
*   Basic Tailwind link styling.

**Interaction:** Displays user name, conditionally linking to their profile.

*(Further view analysis pending...)*

### Pitch Status Banner Component (`pitch-status.blade.php`)

**File:** `resources/views/components/pitch-status.blade.php`

**Purpose:** Renders a prominent banner displaying a formatted pitch status with dynamic background and text colors.

**Usage:** Likely used via `@include('components.pitch-status', ['status' => $statusString, 'bgColor' => $tailwindBgClass, 'textColor' => $tailwindTextClass])`

**Input Props (Implicit):**
*   `$status`: The pitch status string (e.g., 'ready_for_review').
*   `$bgColor`: A Tailwind background color class (e.g., 'bg-blue-100').
*   `$textColor`: A Tailwind text color class (e.g., 'text-blue-800').

**Key Features:**
*   Formats the `$status` string using `ucwords(str_replace('_', ' ', $status))` for display.
*   Dynamically applies background and text color classes passed via `$bgColor` and `$textColor` using `@apply`.
*   Styled with Tailwind CSS.

**Interaction:** Purely a display component for showing status in a styled banner.

*(Further view analysis pending...)*

### Project Status Button Component (`project-status-button.blade.php`)

**File:** `resources/views/components/project-status-button.blade.php`

**Purpose:** Renders a styled badge or indicator for a project's status, including a relevant icon.

**Usage:** Likely used via `@include('components.project-status-button', ['status' => $project->status, 'type' => 'inline'])` or within Blade component context.

**Input Props (Implicit):**
*   `$status`: The project status string ('unpublished', 'open', 'review', 'completed', 'closed').
*   `$type`: Controls styling variations (e.g., 'top-right' for specific positioning/rounding, defaults to 'inline' styling).

**Key Features:**
*   **Status Mapping:** Uses a PHP `@switch` statement to map project `$status` strings to specific Tailwind CSS classes (`$colorClass`) and Font Awesome icon classes (`$iconClass`).
*   **Custom Colors:** Uses custom color names (e.g., `bg-statusOpen`, `shadow-statusOpen`) presumably defined in Tailwind config or CSS.
*   **Conditional Styling:** Applies different `border-radius` styles based on the `$type` variable.
*   **Display:** Renders a `<span>` containing the icon and the raw `$status` string.
*   **Styling:** Uses Tailwind CSS and Font Awesome.

**Interaction:** Purely a display component for project status indication.

*(Further view analysis pending...)*

### Invoice List View (`invoices.blade.php`)

**File:** `resources/views/billing/invoices.blade.php`

**Purpose:** Displays a paginated list of all the user's invoices.

**Layout:** Uses `x-app-layout` with a header slot ("Billing History") and a back link.

**Data:** Expects an `$invoices` collection (likely paginated Cashier Invoice objects). Uses `session`.

**Structure:**
*   Displays session messages.
*   Shows an empty state if no invoices exist.
*   Displays invoices in a table: Number, Date, Amount, Status (Paid/Unpaid), Type (Pitch Payment/Standard), Actions (View/Download).
*   "Type" column checks metadata for `pitch_id` and links to the project if applicable.
*   Provides "View" and "Download" links per invoice.

**Interaction:** Displays invoice list. Provides links to view/download invoices and navigate to related projects. Assumes pagination if data is paginated.

*(Further view analysis pending...)*

### Billing Diagnostic View (`diagnostic.blade.php`)

**File:** `resources/views/billing/diagnostic.blade.php`

**Purpose:** Developer/admin tool to diagnose invoice synchronization issues by comparing raw Stripe invoice data with Cashier's local count.

**Layout:** Uses `x-app-layout` with a header slot ("Invoice Diagnostic") and a back link.

**Data:** Expects `$stripeId`, `$cashierInvoiceCount`, `$rawInvoiceData` (array of raw Stripe invoices).

**Structure:**
*   Displays Stripe Customer ID, Cashier invoice count, raw Stripe invoice count.
*   Shows a table of raw invoice data fetched directly from Stripe.
*   Provides a list of debugging steps, including suggesting an Artisan command (`stripe:sync-invoices --all`).
*   Includes a "Return to Billing" link.

**Interaction:** Static display of diagnostic information and debugging steps.

*(Further view analysis pending...)*

### Payment Methods Management View (`payment-methods.blade.php`)

**File:** `resources/views/billing/payment-methods.blade.php`

**Purpose:** Allows users to view all saved payment methods, set a default, add new ones, and remove existing ones.

**Layout:** Uses `x-app-layout` with a header slot ("Payment Methods").

**Data:** Expects `$paymentMethods` (collection), `$defaultPaymentMethod`, `$intent` (Stripe SetupIntent). Uses `session`.

**Structure:**
*   Displays session messages.
*   "Add New Card" button toggles a hidden Stripe Elements form.
*   Lists existing payment methods with icon, brand, last 4, expiry.
*   Highlights the default method.
*   Provides "Make Default" button (POST form) for non-default methods.
*   Provides "Remove" button (DELETE form with JS confirm) for all methods.
*   Shows empty state message.
*   Footer links to Billing overview and Stripe Portal.

**JavaScript (`@push('scripts')`):**
*   Includes Stripe.js.
*   Initializes Stripe, mounts Card Element, handles errors.
*   Handles add card form submission using `confirmCardSetup` with SetupIntent, then submits to backend.
*   Toggles add card form visibility.

**Interaction:** Manages multiple payment methods, integrates Stripe Elements for adding cards, uses forms for setting default/removing.

*(Further view analysis pending...)*

### Checkout View (`checkout.blade.php`)

**File:** `resources/views/billing/checkout.blade.php`

**Purpose:** Provides a dedicated page for handling payments, likely for subscriptions or other purchases initiated via Cashier's `checkout`. Focuses solely on collecting payment details.

**Layout:** Uses `x-app-layout` with a header slot ("Checkout").

**Data:** Expects `$intent` (Stripe SetupIntent) and potentially `$priceId`. Uses `session`.

**Structure:**
*   Displays session messages.
*   Contains a single form POSTing to `billing.payment.process`.
*   Includes hidden `price_id` (if provided).
*   Includes Stripe Card Element (`#card-element`) and error display (`#card-errors`).
*   Submit button holds SetupIntent secret.
*   Provides a "Back to Billing" link.

**JavaScript (`@push('scripts')`):**
*   Includes Stripe.js.
*   Initializes Stripe, mounts Card Element, handles errors.
*   Handles form submission using `confirmCardSetup` with SetupIntent, then submits payment method ID and price ID to backend.

**Interaction:** Streamlined checkout page using Stripe Elements and SetupIntents to capture card details for payment processing.

*(Further view analysis pending...)*

### Detailed Invoice View (`invoice-details.blade.php` - Potentially Redundant)

**File:** `resources/views/billing/invoice-details.blade.php`

**Note:** This is a **full page view**, not the component `components/invoice-details.blade.php`. It appears redundant and potentially unused, as `invoice-show.blade.php` uses the component.

**Purpose:** Displays detailed invoice information, including line items, summary, payment method, and billing address.

**Layout:** Uses `x-app-layout` with header including back/download links.

**Data:** Expects `$invoice` (Cashier Invoice object). Uses `session`.

**Structure:**
*   Displays session messages.
*   Shows Invoice Header (Number, Date, Status).
*   Shows Summary (Subtotal, Tax, Discount, Total).
*   Shows Line Items table.
*   **Contains logic within the view** to fetch related Stripe PaymentMethod (for paid invoices) and Customer objects using the Stripe SDK.
*   Displays Payment Method details (Card Brand, Last 4, Expiry).
*   Displays Billing Details (Name, Email, Address).

**Interaction:** Static display of detailed invoice info. Includes logic within the view to fetch related Stripe data.

**Potential Issues:**
*   Likely redundant due to `invoice-show.blade.php` using the `x-invoice-details` component.
*   Contains business logic (Stripe SDK calls) within the view, violating separation of concerns.

*(Further view analysis pending...)*

## Email Views (`resources/views/emails/`)

### Email Test Form View (`test-form.blade.php`)

**File:** `resources/views/emails/test-form.blade.php`

**Purpose:** Provides a simple form for sending a test email (likely corresponds to `EmailController::showTestForm`).

**Layout:** Uses `x-app-layout` with a "Test Email" header.

**Data:** Uses `session` and `@error` directive.

**Structure:**
*   Displays session success/error messages.
*   Simple form POSTing to `email.test.send`.
*   Includes `@csrf`.
*   Input field for recipient email (`name="email"`) with validation error display.
*   "Send Test Email" submit button.

**Interaction:** User enters email and submits form to trigger sending of a predefined test email via the backend.

*(Further view analysis pending...)*

### Test Email Template (`test.blade.php`)

**File:** `resources/views/emails/test.blade.php`

**Purpose:** Defines the HTML structure and content for the test email sent via the `TestMail` Mailable.

**Layout:** Standalone HTML file with basic inline CSS. Does not use standard Laravel email components/markdown.

**Data:** Uses `date('Y')`.

**Structure:**
*   Simple centered container.
*   Static heading and text confirming email configuration (mentioning SES) is working.
*   Basic footer with copyright.

**Interaction:** Static email content.

*(Further view analysis pending...)*

### Jetstream Team Invitation Email (`team-invitation.blade.php`)

**File:** `resources/views/emails/team-invitation.blade.php`

**Purpose:** Standard Jetstream email template sent when a user is invited to a team.

**Layout:** Uses Laravel Markdown Mail components (`mail::message`, `mail::button`).

**Data:** Expects `$invitation` (Jetstream object) and `$acceptUrl`. Uses Fortify features check.

**Structure:**
*   States which team the user is invited to.
*   Conditionally includes a "Create Account" button if registration is enabled.
*   Includes an "Accept Invitation" button linking to `$acceptUrl`.
*   Includes discard instructions.

**Interaction:** Provides buttons to create account/accept invitation.

*(Further view analysis pending...)*

### Payment Receipt Email (`payment/receipt.blade.php`)

**File:** `resources/views/emails/payment/receipt.blade.php`

**Purpose:** Email template sent to both project owner and pitch creator upon successful payment or project completion (for free projects).

**Layout:** Standalone HTML file with basic inline CSS.

**Data:** Expects `$isFreeProject`, `$recipientName`, `$recipientType`, `$project`, `$pitch`, `$paymentDate`, `$invoiceId`, `$amount`.

**Structure:**
*   Mixpitch logo and title.
*   Greeting and text confirming payment/completion, tailored to recipient.
*   Summary box: Project Name, Pitch ID, Date, Invoice ID (if applicable), Amount/"Free Project".
*   Concluding message tailored to recipient.
*   Button linking to the web receipt view (`projects.pitches.payment.receipt`).
*   Standard footer.

**Interaction:** Confirmation email with link to web receipt.

*(Further view analysis pending...)*

## Filament Views (`resources/views/filament/`)

**Purpose:** Contains Blade views used by the Filament Admin Panel.

**Structure:** Organized into subdirectories like `pages`, `resources`, `widgets`, etc., mirroring Filament concepts.

**Analysis:** Views generally follow standard Filament patterns:
*   Use Filament layout components (e.g., `<x-filament::page>`).
*   Render widgets using `<x-filament-widgets::widgets />` based on configurations in PHP classes.
*   Embed Livewire components for specific functionality (e.g., `<livewire:email-test-form />` in `test-email-page.blade.php`).
*   Minimal custom Blade logic; complexity resides in corresponding `app/Filament/` PHP classes and embedded Livewire components.

**(Skipping detailed analysis of individual files as they follow standard Filament structure).**

*(Further view analysis pending...)*

## Vendor Views (`resources/views/vendor/`)

This directory is **empty**. No third-party package views have been published for customization.

*(Further view analysis pending...)*

## User Profile Views (`resources/views/user-profile/` & `resources/views/profile/`)

### Public Profile View (`user-profile/show.blade.php`)

**File:** `resources/views/user-profile/show.blade.php`

**Purpose:** Displays the public profile page for a given user.

**Layout:** Uses `x-app-layout`.

**Data:** Expects `$user`, `$canEdit` (boolean), `$projects` (collection), `$completedPitches` (collection).

**Structure:**
*   **Profile Header:** Avatar, Name, Username, Headline, Location, Website. Conditional "Edit Profile" button. Social media links, Tipjar link.
*   **Main Content Grid (2 columns):**
    *   **Left:** About (Bio, conditional Tipjar button), Skills/Equipment/Specialties (displayed as badges).
    *   **Right:** User's public Projects grid, Completed Pitches list.

**Interaction:** Displays profile info, projects, completed work. Includes external links (social, website, tipjar) and edit link for owner.

*(Further view analysis pending...)*

### Profile Edit View (Livewire) (`user-profile/edit-livewire.blade.php`)

**File:** `resources/views/user-profile/edit-livewire.blade.php`

**Purpose:** Container view that loads the `UserProfileEdit` Livewire component for editing the authenticated user's profile.

**Layout:** Uses `x-app-layout`.

**Data:** None passed directly; Livewire component fetches authenticated user.

**Structure:** Includes `<livewire:user-profile-edit />`.

**Interaction:** All logic handled by the embedded `UserProfileEdit` Livewire component.

*(Further view analysis pending...)*

### Profile Edit View (Standard Form - Potentially Redundant) (`user-profile/edit.blade.php`)

**File:** `resources/views/user-profile/edit.blade.php`

**Note:** This appears to be a standard HTML form implementation for profile editing, likely redundant due to the Livewire version (`user-profile/edit-livewire.blade.php` loading `UserProfileEdit`).

**Purpose:** Allows authenticated user to edit profile details via a standard form.

**Layout:** Uses `x-app-layout` with "Edit Profile" header and conditional public profile link.

**Data:** Expects `$user`. Uses `session`, `@error`, `old()`.

**Structure:**
*   Displays session messages and username setup prompt.
*   Standard HTML form POSTing to `profile.update` (PUT method).
*   Inputs for Username, Bio, Website, Location.
*   Inputs for Social Links (Twitter, Instagram, SoundCloud, Spotify, YouTube) using array format (`social_links[platform]`). Attempts to use `$this->getSocialUsername()` for pre-filling, suggesting it might have been intended for a Livewire component or requires a helper method.
*   "Save" button.

**Interaction:** Standard HTML form submission to `UserProfileController::update`.

**Redundancy:** Likely redundant/unused in favor of the Livewire implementation.

*(Further view analysis pending...)*

### Jetstream Profile Views (`resources/views/profile/`)

**Purpose:** Contains standard Jetstream views for user profile management (update info, password, 2FA, browser sessions, delete account).

**Structure:** The main view (`show.blade.php`) uses `x-app-layout` and conditionally includes various `@livewire('profile.*')` components based on enabled Jetstream/Fortify features. Other files in the directory are the Blade views for these embedded Livewire components.

**Interaction:** All logic is handled by the embedded Jetstream Livewire components.

**(Skipping detailed analysis of individual files as they are standard Jetstream components).**

*(Further view analysis pending...)*

## Pitch Files Views (`resources/views/pitch-files/`)

### Pitch File Show View (`show.blade.php`)

**File:** `resources/views/pitch-files/show.blade.php`

**Purpose:** Container view to display a single pitch file using the `PitchFilePlayer` Livewire component.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$file` (PitchFile model).

**Structure:**
*   Includes `<livewire:pitch-file-player :file="$file" />`.
*   Pushes basic CSS for waveform loading/timeline.
*   Pushes Wavesurfer.js library.

**Interaction:** All logic handled by the embedded `PitchFilePlayer` Livewire component.

*(Further view analysis pending...)*

## Authentication Views (`resources/views/auth/`)

**Purpose:** Contains standard Laravel Fortify views for handling authentication flows (login, registration, password reset, two-factor auth, etc.).

**Structure:** Views use `x-guest-layout` and standard Jetstream/Fortify Blade components (e.g., `x-authentication-card`). They correspond to Fortify's backend routes and actions.

**Interaction:** Standard HTML forms submitting to Fortify controllers/actions.

**(Skipping detailed analysis of individual files as they are standard Fortify views).**

*(Further view analysis pending...)*

## API Token Views (`resources/views/api/`)

**Purpose:** Contains standard Jetstream views for API token management.

**Structure:** The main view (`index.blade.php`) uses `x-app-layout` and includes the `@livewire('api.api-token-manager')` component. The other file (`api-token-manager.blade.php`) is the view for that Livewire component.

**Interaction:** All logic handled by the embedded Jetstream Livewire component.

**(Skipping detailed analysis as they are standard Jetstream components).**

*(Further view analysis pending...)*

## Projects Views (`resources/views/projects/`)

### Public Project Show View (`project.blade.php`)

**File:** `resources/views/projects/project.blade.php`

**Purpose:** Displays the public detail page for a single project.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project` (with relations), `$canPitch` (boolean), `$userPitch` (Pitch model or null). Uses `Auth`.

**Structure:**
*   **Header Card:** Project image (Alpine lightbox), preview track audio player (Livewire), Project Name, Artist, Owner (`x-user-link`), Status (`x-project-status-button`), Type, Deadline. Conditional buttons (Manage Project / Start Pitch / View Pitch).
*   **Details Section:** Collaboration badges, Budget, Description, Notes.

**Interaction:** Displays project details. Conditional actions for owner (Manage) or others (Start/View Pitch). Pitch initiation uses JS modal (`openPitchTermsModal()`). Includes audio player and image lightbox.

*(Further view analysis pending...)*

### Project Edit View (Standard Form - Potentially Redundant) (`edit.blade.php`)

**File:** `resources/views/projects/edit.blade.php`

**Note:** Likely redundant due to the `ManageProject` Livewire component handling project editing and file management.

**Purpose:** Allows editing project details and managing files via standard HTML forms.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project` (with relations). Uses `old()`.

**Structure:**
*   **Header:** Displays project image (clickable for upload via JS), inputs for Name, Description, Genre, Status.
*   **Form:** Standard HTML form POSTing to `projects.update` (PUT method), `enctype="multipart/form-data"`.
*   **File Management:** Lists existing files with Delete buttons (individual DELETE forms triggered by JS). Links to a separate `projects.createStep2` route for uploads.

**JavaScript:** Includes `loadFile` for image preview and event listener for delete buttons.

**Interaction:** Standard form submission for edits. File deletion via separate forms triggered by JS. Links to potentially deprecated upload step.

**Redundancy:** Likely redundant/unused in favor of the `ManageProject` Livewire implementation.

### Project Index View (`index.blade.php` - Livewire Wrapper)

**File:** `resources/views/projects/index.blade.php`

**Purpose:** Container view for the public project listing.

**Layout:** Extends `components.layouts.app`.

**Structure:** Includes `<livewire:projects-component />`. Assumes the listing logic is handled by this Livewire component.

### Project Upload (Step 2) View (`create_step2.blade.php` - Likely Deprecated)

**File:** `resources/views/projects/create_step2.blade.php`

**Purpose:** Provides a Dropzone.js interface for uploading files as part of a multi-step project creation/edit process.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project`.

**Structure:** Includes Dropzone CSS/JS. Contains Dropzone form POSTing to `projects.storeStep2`. Includes Back/Finish links.

**JavaScript:** Initializes Dropzone.

**Redundancy:** Uses Dropzone and separate upload step, contrasting with integrated S3 uploads in `ManageProject`. Highly likely deprecated.

### Upload Project View (`upload-project.blade.php` - TrackController related)

**File:** `resources/views/projects/upload-project.blade.php`

**Purpose:** Container view, likely related to the `TrackController` functionality.

**Layout:** Extends `components.layouts.app`.

**Structure:** Includes `<livewire:upload-project-component />`. Logic is assumed to be in this Livewire component.

*(Further view analysis pending...)*

## Components (`resources/views/components/`)

### Invoice Details Component (`invoice-details.blade.php`)

**File:** `resources/views/components/invoice-details.blade.php`

**Purpose:** Renders a detailed view of a single invoice, likely using data fetched from Stripe.

**Usage:** Expected to be used like `<x-invoice-details :invoice="$invoiceObject" :view-all-url="route(...)" />`

**Input Props:**
*   `$invoice`: An object containing invoice data (ID/number, status, date, total, description, line items). Structure suggests compatibility with Stripe API responses or formatted objects from `InvoiceService`.
*   `$viewAllUrl` (optional): A URL string for a 'View all invoices' link.

**Key Features:**
*   Displays invoice number, paid/unpaid status badge, date, and total amount.
*   Lists line items from `$invoice->lines->data` or shows a single summary line.
*   Provides a 'Download PDF' link to `route('billing.invoice.download', $invoice->id)`.
*   Optionally provides a 'View all invoices' link.
*   Styled with Tailwind CSS.

**Interaction:** Primarily a display component formatting invoice data. Provides navigation links for downloading and viewing other invoices.

*(Further view analysis pending...)*

### Update Pitch Status Buttons Component (`update-pitch-status.blade.php`)

**File:** `resources/views/components/update-pitch-status.blade.php`

**Purpose:** Renders action buttons for changing a pitch's status based on its current state.
**Note: This component appears highly problematic and likely redundant/deprecated.** Its logic seems to belong within the `UpdatePitchStatus` Livewire component's view (`resources/views/livewire/pitch/component/update-pitch-status.blade.php`). This Blade component bypasses the Livewire component's actions and service layer calls in many cases.

**Usage:** Likely intended usage `<x-update-pitch-status :pitch="$pitch" />`

**Input Props:**
*   `$pitch`: The `Pitch` model.

**Key Features & Issues:**
*   **Conditional Logic:** Uses extensive `@if/@elseif` based on `$pitch->status` to determine which actions/buttons to show.
*   **Direct Form Submissions (Issue):** For several statuses (Pending, In Progress, Approved, Denied, Revisions Requested, Completed), it renders HTML forms that POST directly to controller routes (`projects.pitches.change-status`, `projects.pitches.return-to-approved`). This bypasses the `UpdatePitchStatus` Livewire component and the `PitchWorkflowService`, potentially leading to inconsistent state management and authorization issues.
*   **JavaScript Modal Triggers (Partial Alignment):** For the `READY_FOR_REVIEW` status, it renders buttons that trigger JavaScript functions (`openApproveModal`, etc.) presumably defined elsewhere to interact with modals (`<x-pitch-action-modals />`). This aligns partially with the Livewire component's modal interaction but still passes controller routes directly to JS instead of using Livewire actions.
*   **Includes Modals:** Includes `<x-pitch-action-modals />`.

**Interaction:** Renders conditional buttons/forms. Interacts via direct form POSTs or JavaScript calls, largely bypassing the corresponding Livewire component. Includes a modal component.

**Recommendation:** This component should be reviewed and likely removed. Its logic needs to be consolidated within the `UpdatePitchStatus` Livewire component and its associated view to ensure consistent use of the `PitchWorkflowService` and proper state management.

*(Further view analysis pending...)*

### Pitch Action Modals Component (`pitch-action-modals.blade.php`)

**File:** `resources/views/components/pitch-action-modals.blade.php`

**Purpose:** Defines the HTML structure for modals used for Approve, Deny, and Request Revisions actions on pitches.

**Usage:** Included by other components, e.g., `<x-pitch-action-modals />` (as seen in `update-pitch-status.blade.php`).

**Input Props:** None.

**Key Features:**
*   **Structure:** Contains three distinct modal sections (`#approveModal`, `#denyModal`, `#revisionsModal`), initially hidden.
*   **Forms:** Each modal contains a basic, hidden HTML form (`#approveForm`, etc.) with only `@csrf`. JavaScript is expected to set the `action` URL and potentially add data before submission.
*   **Inputs:** Provides textareas for denial reason (`#denyReason`) and revision requests (`#revisionsRequested`).
*   **Buttons:** Includes Cancel buttons (`onclick="closeModal(...)"`) and confirmation buttons (`#approveSubmitBtn`, etc.) presumably handled by JavaScript.
*   **JavaScript Dependency:** Includes `<script src="{{ asset('js/pitch-modals.js') }}"></script>`, indicating all modal interaction logic (opening, closing, submitting the hidden forms) resides in this external JS file.
*   **Styling:** Uses Tailwind CSS.

**Interaction:** Provides the static modal HTML. Relies entirely on `pitch-modals.js` (and functions like `openApproveModal`, `closeModal`) for display logic and form submission, targeting controller routes directly. This approach is less integrated with Livewire/Alpine compared to using Livewire components for modal state and actions.

**Note:** The reliance on external JS to manipulate and submit hidden forms directly to controller routes is part of the potentially problematic pattern identified in `components/update-pitch-status.blade.php`.

*(Further view analysis pending...)*

### Pitch Terms Modal Component (`pitch-terms-modal.blade.php`)

**File:** `resources/views/components/pitch-terms-modal.blade.php`

**Purpose:** Displays a modal with terms highlights that must be agreed to before initiating a pitch creation request.

**Usage:** Likely included on project detail pages, e.g., `<x-pitch-terms-modal :project="$project" />`. Triggered via JavaScript (`openPitchTermsModal()`).

**Input Props:**
*   `$project`: The `Project` model the user wants to pitch for.

**Key Features:**
*   **Structure:** Defines a modal (`#pitch-terms-modal`) with header, body (terms highlights, full terms link, agreement checkbox), and footer.
*   **Form:** Contains a hidden HTML form (`#pitch-create-form`) targeting `route('projects.pitches.store')` via POST. Includes `@csrf` and `$project->id`.
*   **Agreement:** Requires user to check an 'Agree to Terms' checkbox (`#agree_terms`).
*   **JavaScript:** Includes inline JS defining global functions (`openPitchTermsModal`, `closePitchTermsModal`, `submitPitchForm`) for modal control and form submission. `submitPitchForm` validates checkbox before submitting.
*   **Styling:** Uses Tailwind CSS.

**Interaction:** Provides the UI flow for agreeing to terms before creating a pitch. Uses inline JavaScript for modal display and conditional form submission. Submits directly to a controller route upon confirmation.

*(Further view analysis pending...)*

### User Link Component (`user-link.blade.php`)

**File:** `resources/views/components/user-link.blade.php`

**Purpose:** Renders a user's name, providing a link to their public profile if they have a username.

**Usage:** `<x-user-link :user="$userObject" />`

**Input Props:**
*   `$user`: The `User` model instance.

**Key Features:**
*   Conditionally renders an anchor tag linking to `route('profile.username', ['username' => $user->username])` if `$user->username` is set.
*   Displays the `$user->name` as the link text or plain text.
*   Basic Tailwind link styling.

**Interaction:** Displays user name, conditionally linking to their profile.

*(Further view analysis pending...)*

### Pitch Status Banner Component (`pitch-status.blade.php`)

**File:** `resources/views/components/pitch-status.blade.php`

**Purpose:** Renders a prominent banner displaying a formatted pitch status with dynamic background and text colors.

**Usage:** Likely used via `@include('components.pitch-status', ['status' => $statusString, 'bgColor' => $tailwindBgClass, 'textColor' => $tailwindTextClass])`

**Input Props (Implicit):**
*   `$status`: The pitch status string (e.g., 'ready_for_review').
*   `$bgColor`: A Tailwind background color class (e.g., 'bg-blue-100').
*   `$textColor`: A Tailwind text color class (e.g., 'text-blue-800').

**Key Features:**
*   Formats the `$status` string using `ucwords(str_replace('_', ' ', $status))` for display.
*   Dynamically applies background and text color classes passed via `$bgColor` and `$textColor` using `@apply`.
*   Styled with Tailwind CSS.

**Interaction:** Purely a display component for showing status in a styled banner.

*(Further view analysis pending...)*

### Project Status Button Component (`project-status-button.blade.php`)

**File:** `resources/views/components/project-status-button.blade.php`

**Purpose:** Renders a styled badge or indicator for a project's status, including a relevant icon.

**Usage:** Likely used via `@include('components.project-status-button', ['status' => $project->status, 'type' => 'inline'])` or within Blade component context.

**Input Props (Implicit):**
*   `$status`: The project status string ('unpublished', 'open', 'review', 'completed', 'closed').
*   `$type`: Controls styling variations (e.g., 'top-right' for specific positioning/rounding, defaults to 'inline' styling).

**Key Features:**
*   **Status Mapping:** Uses a PHP `@switch` statement to map project `$status` strings to specific Tailwind CSS classes (`$colorClass`) and Font Awesome icon classes (`$iconClass`).
*   **Custom Colors:** Uses custom color names (e.g., `bg-statusOpen`, `shadow-statusOpen`) presumably defined in Tailwind config or CSS.
*   **Conditional Styling:** Applies different `border-radius` styles based on the `$type` variable.
*   **Display:** Renders a `<span>` containing the icon and the raw `$status` string.
*   **Styling:** Uses Tailwind CSS and Font Awesome.

**Interaction:** Purely a display component for project status indication.

*(Further view analysis pending...)*

### Invoice List View (`invoices.blade.php`)

**File:** `resources/views/billing/invoices.blade.php`

**Purpose:** Displays a paginated list of all the user's invoices.

**Layout:** Uses `x-app-layout` with a header slot ("Billing History") and a back link.

**Data:** Expects an `$invoices` collection (likely paginated Cashier Invoice objects). Uses `session`.

**Structure:**
*   Displays session messages.
*   Shows an empty state if no invoices exist.
*   Displays invoices in a table: Number, Date, Amount, Status (Paid/Unpaid), Type (Pitch Payment/Standard), Actions (View/Download).
*   "Type" column checks metadata for `pitch_id` and links to the project if applicable.
*   Provides "View" and "Download" links per invoice.

**Interaction:** Displays invoice list. Provides links to view/download invoices and navigate to related projects. Assumes pagination if data is paginated.

*(Further view analysis pending...)*

### Billing Diagnostic View (`diagnostic.blade.php`)

**File:** `resources/views/billing/diagnostic.blade.php`

**Purpose:** Developer/admin tool to diagnose invoice synchronization issues by comparing raw Stripe invoice data with Cashier's local count.

**Layout:** Uses `x-app-layout` with a header slot ("Invoice Diagnostic") and a back link.

**Data:** Expects `$stripeId`, `$cashierInvoiceCount`, `$rawInvoiceData` (array of raw Stripe invoices).

**Structure:**
*   Displays Stripe Customer ID, Cashier invoice count, raw Stripe invoice count.
*   Shows a table of raw invoice data fetched directly from Stripe.
*   Provides a list of debugging steps, including suggesting an Artisan command (`stripe:sync-invoices --all`).
*   Includes a "Return to Billing" link.

**Interaction:** Static display of diagnostic information and debugging steps.

*(Further view analysis pending...)*

### Payment Methods Management View (`payment-methods.blade.php`)

**File:** `resources/views/billing/payment-methods.blade.php`

**Purpose:** Allows users to view all saved payment methods, set a default, add new ones, and remove existing ones.

**Layout:** Uses `x-app-layout` with a header slot ("Payment Methods").

**Data:** Expects `$paymentMethods` (collection), `$defaultPaymentMethod`, `$intent` (Stripe SetupIntent). Uses `session`.

**Structure:**
*   Displays session messages.
*   "Add New Card" button toggles a hidden Stripe Elements form.
*   Lists existing payment methods with icon, brand, last 4, expiry.
*   Highlights the default method.
*   Provides "Make Default" button (POST form) for non-default methods.
*   Provides "Remove" button (DELETE form with JS confirm) for all methods.
*   Shows empty state message.
*   Footer links to Billing overview and Stripe Portal.

**JavaScript (`@push('scripts')`):**
*   Includes Stripe.js.
*   Initializes Stripe, mounts Card Element, handles errors.
*   Handles add card form submission using `confirmCardSetup` with SetupIntent, then submits to backend.
*   Toggles add card form visibility.

**Interaction:** Manages multiple payment methods, integrates Stripe Elements for adding cards, uses forms for setting default/removing.

*(Further view analysis pending...)*

### Checkout View (`checkout.blade.php`)

**File:** `resources/views/billing/checkout.blade.php`

**Purpose:** Provides a dedicated page for handling payments, likely for subscriptions or other purchases initiated via Cashier's `checkout`. Focuses solely on collecting payment details.

**Layout:** Uses `x-app-layout` with a header slot ("Checkout").

**Data:** Expects `$intent` (Stripe SetupIntent) and potentially `$priceId`. Uses `session`.

**Structure:**
*   Displays session messages.
*   Contains a single form POSTing to `billing.payment.process`.
*   Includes hidden `price_id` (if provided).
*   Includes Stripe Card Element (`#card-element`) and error display (`#card-errors`).
*   Submit button holds SetupIntent secret.
*   Provides a "Back to Billing" link.

**JavaScript (`@push('scripts')`):**
*   Includes Stripe.js.
*   Initializes Stripe, mounts Card Element, handles errors.
*   Handles form submission using `confirmCardSetup` with SetupIntent, then submits payment method ID and price ID to backend.

**Interaction:** Streamlined checkout page using Stripe Elements and SetupIntents to capture card details for payment processing.

*(Further view analysis pending...)*

### Detailed Invoice View (`invoice-details.blade.php` - Potentially Redundant)

**File:** `resources/views/billing/invoice-details.blade.php`

**Note:** This is a **full page view**, not the component `components/invoice-details.blade.php`. It appears redundant and potentially unused, as `invoice-show.blade.php` uses the component.
# Views (`resources/views/`)

This section documents the Blade views responsible for rendering the application's UI.

## Layouts

### Main Application Layout (`app.blade.php`)

**File:** `resources/views/components/layouts/app.blade.php`

**Purpose:** Serves as the primary HTML structure and layout wrapper for most authenticated application pages and potentially some public pages.

**Key Features & Technologies:**
*   **Structure:** Standard HTML5, `min-h-screen` flex layout pushing footer down.
*   **Styling:**
    *   Uses Vite for `resources/css/app.css` (likely includes Tailwind CSS).
    *   Utilizes DaisyUI theme (`data-theme="main"`).
    *   Includes custom CSS (`public/css/custom.css`, `public/css/homepage.css`, `public/css/star-rating.css`).
    *   Includes Google Fonts (`Inter`).
    *   Includes `@livewireStyles`.
    *   Basic Alpine.js `x-cloak` style.
*   **JavaScript:**
    *   Uses Vite for `resources/js/app.js` (likely includes Alpine.js).
    *   Includes `@livewireScripts()`.
    *   Includes `wavesurfer.js` globally.
    *   Includes jQuery, Popper.js, and potentially conflicting Bootstrap 4/5 JS versions.
    *   Supports page-specific scripts via `@yield('scripts')` and `@stack('scripts')`.
    *   Includes `<x-toaster-hub />` for notifications.
*   **Partials:**
    *   Includes navigation: `@include('components.layouts.navigation')`.
    *   Includes footer: `@include('components.layouts.footer')`.
*   **Content Sections:**
    *   Renders main page content via `$slot` (for Livewire v3 layout usage) or `@yield('content')` (for traditional `@extends`).
    *   Optionally displays a page header via the `$header` slot.

**Interaction:**
*   Provides the consistent shell (header, footer, base styles, global JS) for pages extending it or using it as a Livewire layout.
*   Loads necessary assets via Vite and direct links.
*   Integrates Livewire, Alpine.js, Wavesurfer.js, and Toaster.

### Navigation Partial (`navigation.blade.php`)

**File:** `resources/views/components/layouts/navigation.blade.php`

**Purpose:** Renders the main site navigation bar, included by the `app.blade.php` layout.

**Key Features:**
*   **Responsive:** Uses Alpine.js for mobile hamburger menu functionality.
*   **Styling:** Styled with Tailwind/DaisyUI, highlights active links based on current route.
*   **Content (Desktop):** Logo, Projects, Pricing, About links. Auth section shows NotificationList/AuthDropdown (Livewire) if logged in, or Login/Register links if guest.
*   **Content (Mobile):** Logo, hamburger. Expanded menu shows Projects, Pricing, About, Dashboard. Conditional links for Admin Dashboard and Profile Setup. Auth section shows user info, profile/settings links, logout button if logged in, or Login/Register buttons if guest.
*   **Livewire Integration:** Embeds `NotificationList` and `AuthDropdown` components.
*   **Authorization:** Uses `Auth::check()`, `Auth::user()->canAccessPanel()`, `Auth::user()->hasCompletedProfile()` for conditional display.

**Interaction:** Provides consistent navigation across the site. Integrates Livewire components for dynamic elements. Adapts based on authentication status and user permissions/profile completion.

### Footer Partial (`footer.blade.php`)

**File:** `resources/views/components/layouts/footer.blade.php`

**Purpose:** Renders the site footer, included by the `app.blade.php` layout.

**Key Features:**
*   **Structure:** Multi-column layout for Quick Links, Social Media, Newsletter signup.
*   **Styling:** Basic Tailwind styling.
*   **Content:** Static links (some placeholders), social media icons (Font Awesome, placeholder links), newsletter form (placeholder action), copyright notice.

**Interaction:** Provides static footer content and links.

*(Further view analysis pending...)*

## Custom Blade Components (`resources/views/components/`)

### Invoice Details Component (`invoice-details.blade.php`)

**File:** `resources/views/components/invoice-details.blade.php`

**Purpose:** Renders a detailed view of a single invoice, likely using data fetched from Stripe.

**Usage:** Expected to be used like `<x-invoice-details :invoice="$invoiceObject" :view-all-url="route(...)" />`

**Input Props:**
*   `$invoice`: An object containing invoice data (ID/number, status, date, total, description, line items). Structure suggests compatibility with Stripe API responses or formatted objects from `InvoiceService`.
*   `$viewAllUrl` (optional): A URL string for a 'View all invoices' link.

**Key Features:**
*   Displays invoice number, paid/unpaid status badge, date, and total amount.
*   Lists line items from `$invoice->lines->data` or shows a single summary line.
*   Provides a 'Download PDF' link to `route('billing.invoice.download', $invoice->id)`.
*   Optionally provides a 'View all invoices' link.
*   Styled with Tailwind CSS.

**Interaction:** Primarily a display component formatting invoice data. Provides navigation links for downloading and viewing other invoices.

*(Further view analysis pending...)*

### Update Pitch Status Buttons Component (`update-pitch-status.blade.php`)

**File:** `resources/views/components/update-pitch-status.blade.php`

**Purpose:** Renders action buttons for changing a pitch's status based on its current state.
**Note: This component appears highly problematic and likely redundant/deprecated.** Its logic seems to belong within the `UpdatePitchStatus` Livewire component's view (`resources/views/livewire/pitch/component/update-pitch-status.blade.php`). This Blade component bypasses the Livewire component's actions and service layer calls in many cases.

**Usage:** Likely intended usage `<x-update-pitch-status :pitch="$pitch" />`

**Input Props:**
*   `$pitch`: The `Pitch` model.

**Key Features & Issues:**
*   **Conditional Logic:** Uses extensive `@if/@elseif` based on `$pitch->status` to determine which actions/buttons to show.
*   **Direct Form Submissions (Issue):** For several statuses (Pending, In Progress, Approved, Denied, Revisions Requested, Completed), it renders HTML forms that POST directly to controller routes (`projects.pitches.change-status`, `projects.pitches.return-to-approved`). This bypasses the `UpdatePitchStatus` Livewire component and the `PitchWorkflowService`, potentially leading to inconsistent state management and authorization issues.
*   **JavaScript Modal Triggers (Partial Alignment):** For the `READY_FOR_REVIEW` status, it renders buttons that trigger JavaScript functions (`openApproveModal`, etc.) presumably defined elsewhere to interact with modals (`<x-pitch-action-modals />`). This aligns partially with the Livewire component's modal interaction but still passes controller routes directly to JS instead of using Livewire actions.
*   **Includes Modals:** Includes `<x-pitch-action-modals />`.

**Interaction:** Renders conditional buttons/forms. Interacts via direct form POSTs or JavaScript calls, largely bypassing the corresponding Livewire component. Includes a modal component.

**Recommendation:** This component should be reviewed and likely removed. Its logic needs to be consolidated within the `UpdatePitchStatus` Livewire component and its associated view to ensure consistent use of the `PitchWorkflowService` and proper state management.

*(Further view analysis pending...)*

### Pitch Action Modals Component (`pitch-action-modals.blade.php`)

**File:** `resources/views/components/pitch-action-modals.blade.php`

**Purpose:** Defines the HTML structure for modals used for Approve, Deny, and Request Revisions actions on pitches.

**Usage:** Included by other components, e.g., `<x-pitch-action-modals />` (as seen in `update-pitch-status.blade.php`).

**Input Props:** None.

**Key Features:**
*   **Structure:** Contains three distinct modal sections (`#approveModal`, `#denyModal`, `#revisionsModal`), initially hidden.
*   **Forms:** Each modal contains a basic, hidden HTML form (`#approveForm`, etc.) with only `@csrf`. JavaScript is expected to set the `action` URL and potentially add data before submission.
*   **Inputs:** Provides textareas for denial reason (`#denyReason`) and revision requests (`#revisionsRequested`).
*   **Buttons:** Includes Cancel buttons (`onclick="closeModal(...)"`) and confirmation buttons (`#approveSubmitBtn`, etc.) presumably handled by JavaScript.
*   **JavaScript Dependency:** Includes `<script src="{{ asset('js/pitch-modals.js') }}"></script>`, indicating all modal interaction logic (opening, closing, submitting the hidden forms) resides in this external JS file.
*   **Styling:** Uses Tailwind CSS.

**Interaction:** Provides the static modal HTML. Relies entirely on `pitch-modals.js` (and functions like `openApproveModal`, `closeModal`) for display logic and form submission, targeting controller routes directly. This approach is less integrated with Livewire/Alpine compared to using Livewire components for modal state and actions.

**Note:** The reliance on external JS to manipulate and submit hidden forms directly to controller routes is part of the potentially problematic pattern identified in `components/update-pitch-status.blade.php`.

*(Further view analysis pending...)*

### Pitch Terms Modal Component (`pitch-terms-modal.blade.php`)

**File:** `resources/views/components/pitch-terms-modal.blade.php`

**Purpose:** Displays a modal with terms highlights that must be agreed to before initiating a pitch creation request.

**Usage:** Likely included on project detail pages, e.g., `<x-pitch-terms-modal :project="$project" />`. Triggered via JavaScript (`openPitchTermsModal()`).

**Input Props:**
*   `$project`: The `Project` model the user wants to pitch for.

**Key Features:**
*   **Structure:** Defines a modal (`#pitch-terms-modal`) with header, body (terms highlights, full terms link, agreement checkbox), and footer.
*   **Form:** Contains a hidden HTML form (`#pitch-create-form`) targeting `route('projects.pitches.store')` via POST. Includes `@csrf` and `$project->id`.
*   **Agreement:** Requires user to check an 'Agree to Terms' checkbox (`#agree_terms`).
*   **JavaScript:** Includes inline JS defining global functions (`openPitchTermsModal`, `closePitchTermsModal`, `submitPitchForm`) for modal control and form submission. `submitPitchForm` validates checkbox before submitting.
*   **Styling:** Uses Tailwind CSS.

**Interaction:** Provides the UI flow for agreeing to terms before creating a pitch. Uses inline JavaScript for modal display and conditional form submission. Submits directly to a controller route upon confirmation.

*(Further view analysis pending...)*

### User Link Component (`user-link.blade.php`)

**File:** `resources/views/components/user-link.blade.php`

**Purpose:** Renders a user's name, providing a link to their public profile if they have a username.

**Usage:** `<x-user-link :user="$userObject" />`

**Input Props:**
*   `$user`: The `User` model instance.

**Key Features:**
*   Conditionally renders an anchor tag linking to `route('profile.username', ['username' => $user->username])` if `$user->username` is set.
*   Displays the `$user->name` as the link text or plain text.
*   Basic Tailwind link styling.

**Interaction:** Displays user name, conditionally linking to their profile.

*(Further view analysis pending...)*

### Pitch Status Banner Component (`pitch-status.blade.php`)

**File:** `resources/views/components/pitch-status.blade.php`

**Purpose:** Renders a prominent banner displaying a formatted pitch status with dynamic background and text colors.

**Usage:** Likely used via `@include('components.pitch-status', ['status' => $statusString, 'bgColor' => $tailwindBgClass, 'textColor' => $tailwindTextClass])`

**Input Props (Implicit):**
*   `$status`: The pitch status string (e.g., 'ready_for_review').
*   `$bgColor`: A Tailwind background color class (e.g., 'bg-blue-100').
*   `$textColor`: A Tailwind text color class (e.g., 'text-blue-800').

**Key Features:**
*   Formats the `$status` string using `ucwords(str_replace('_', ' ', $status))` for display.
*   Dynamically applies background and text color classes passed via `$bgColor` and `$textColor` using `@apply`.
*   Styled with Tailwind CSS.

**Interaction:** Purely a display component for showing status in a styled banner.

*(Further view analysis pending...)*

### Project Status Button Component (`project-status-button.blade.php`)

**File:** `resources/views/components/project-status-button.blade.php`

**Purpose:** Renders a styled badge or indicator for a project's status, including a relevant icon.

**Usage:** Likely used via `@include('components.project-status-button', ['status' => $project->status, 'type' => 'inline'])` or within Blade component context.

**Input Props (Implicit):**
*   `$status`: The project status string ('unpublished', 'open', 'review', 'completed', 'closed').
*   `$type`: Controls styling variations (e.g., 'top-right' for specific positioning/rounding, defaults to 'inline' styling).

**Key Features:**
*   **Status Mapping:** Uses a PHP `@switch` statement to map project `$status` strings to specific Tailwind CSS classes (`$colorClass`) and Font Awesome icon classes (`$iconClass`).
*   **Custom Colors:** Uses custom color names (e.g., `bg-statusOpen`, `shadow-statusOpen`) presumably defined in Tailwind config or CSS.
*   **Conditional Styling:** Applies different `border-radius` styles based on the `$type` variable.
*   **Display:** Renders a `<span>` containing the icon and the raw `$status` string.
*   **Styling:** Uses Tailwind CSS and Font Awesome.

**Interaction:** Purely a display component for project status indication.

*(Further view analysis pending...)*

### Invoice List View (`invoices.blade.php`)

**File:** `resources/views/billing/invoices.blade.php`

**Purpose:** Displays a paginated list of all the user's invoices.

**Layout:** Uses `x-app-layout` with a header slot ("Billing History") and a back link.

**Data:** Expects an `$invoices` collection (likely paginated Cashier Invoice objects). Uses `session`.

**Structure:**
*   Displays session messages.
*   Shows an empty state if no invoices exist.
*   Displays invoices in a table: Number, Date, Amount, Status (Paid/Unpaid), Type (Pitch Payment/Standard), Actions (View/Download).
*   "Type" column checks metadata for `pitch_id` and links to the project if applicable.
*   Provides "View" and "Download" links per invoice.

**Interaction:** Displays invoice list. Provides links to view/download invoices and navigate to related projects. Assumes pagination if data is paginated.

*(Further view analysis pending...)*

### Billing Diagnostic View (`diagnostic.blade.php`)

**File:** `resources/views/billing/diagnostic.blade.php`

**Purpose:** Developer/admin tool to diagnose invoice synchronization issues by comparing raw Stripe invoice data with Cashier's local count.

**Layout:** Uses `x-app-layout` with a header slot ("Invoice Diagnostic") and a back link.

**Data:** Expects `$stripeId`, `$cashierInvoiceCount`, `$rawInvoiceData` (array of raw Stripe invoices).

**Structure:**
*   Displays Stripe Customer ID, Cashier invoice count, raw Stripe invoice count.
*   Shows a table of raw invoice data fetched directly from Stripe.
*   Provides a list of debugging steps, including suggesting an Artisan command (`stripe:sync-invoices --all`).
*   Includes a "Return to Billing" link.

**Interaction:** Static display of diagnostic information and debugging steps.

*(Further view analysis pending...)*

### Payment Methods Management View (`payment-methods.blade.php`)

**File:** `resources/views/billing/payment-methods.blade.php`

**Purpose:** Allows users to view all saved payment methods, set a default, add new ones, and remove existing ones.

**Layout:** Uses `x-app-layout` with a header slot ("Payment Methods").

**Data:** Expects `$paymentMethods` (collection), `$defaultPaymentMethod`, `$intent` (Stripe SetupIntent). Uses `session`.

**Structure:**
*   Displays session messages.
*   "Add New Card" button toggles a hidden Stripe Elements form.
*   Lists existing payment methods with icon, brand, last 4, expiry.
*   Highlights the default method.
*   Provides "Make Default" button (POST form) for non-default methods.
*   Provides "Remove" button (DELETE form with JS confirm) for all methods.
*   Shows empty state message.
*   Footer links to Billing overview and Stripe Portal.

**JavaScript (`@push('scripts')`):**
*   Includes Stripe.js.
*   Initializes Stripe, mounts Card Element, handles errors.
*   Handles add card form submission using `confirmCardSetup` with SetupIntent, then submits to backend.
*   Toggles add card form visibility.

**Interaction:** Manages multiple payment methods, integrates Stripe Elements for adding cards, uses forms for setting default/removing.

*(Further view analysis pending...)*

### Checkout View (`checkout.blade.php`)

**File:** `resources/views/billing/checkout.blade.php`

**Purpose:** Provides a dedicated page for handling payments, likely for subscriptions or other purchases initiated via Cashier's `checkout`. Focuses solely on collecting payment details.

**Layout:** Uses `x-app-layout` with a header slot ("Checkout").

**Data:** Expects `$intent` (Stripe SetupIntent) and potentially `$priceId`. Uses `session`.

**Structure:**
*   Displays session messages.
*   Contains a single form POSTing to `billing.payment.process`.
*   Includes hidden `price_id` (if provided).
*   Includes Stripe Card Element (`#card-element`) and error display (`#card-errors`).
*   Submit button holds SetupIntent secret.
*   Provides a "Back to Billing" link.

**JavaScript (`@push('scripts')`):**
*   Includes Stripe.js.
*   Initializes Stripe, mounts Card Element, handles errors.
*   Handles form submission using `confirmCardSetup` with SetupIntent, then submits payment method ID and price ID to backend.

**Interaction:** Streamlined checkout page using Stripe Elements and SetupIntents to capture card details for payment processing.

*(Further view analysis pending...)*

### Detailed Invoice View (`invoice-details.blade.php` - Potentially Redundant)

**File:** `resources/views/billing/invoice-details.blade.php`

**Note:** This is a **full page view**, not the component `components/invoice-details.blade.php`. It appears redundant and potentially unused, as `invoice-show.blade.php` uses the component.

**Purpose:** Displays detailed invoice information, including line items, summary, payment method, and billing address.

**Layout:** Uses `x-app-layout` with header including back/download links.

**Data:** Expects `$invoice` (Cashier Invoice object). Uses `session`.

**Structure:**
*   Displays session messages.
*   Shows Invoice Header (Number, Date, Status).
*   Shows Summary (Subtotal, Tax, Discount, Total).
*   Shows Line Items table.
*   **Contains logic within the view** to fetch related Stripe PaymentMethod (for paid invoices) and Customer objects using the Stripe SDK.
*   Displays Payment Method details (Card Brand, Last 4, Expiry).
*   Displays Billing Details (Name, Email, Address).

**Interaction:** Static display of detailed invoice info. Includes logic within the view to fetch related Stripe data.

**Potential Issues:**
*   Likely redundant due to `invoice-show.blade.php` using the `x-invoice-details` component.
*   Contains business logic (Stripe SDK calls) within the view, violating separation of concerns.

*(Further view analysis pending...)*

## Email Views (`resources/views/emails/`)

### Email Test Form View (`test-form.blade.php`)

**File:** `resources/views/emails/test-form.blade.php`

**Purpose:** Provides a simple form for sending a test email (likely corresponds to `EmailController::showTestForm`).

**Layout:** Uses `x-app-layout` with a "Test Email" header.

**Data:** Uses `session` and `@error` directive.

**Structure:**
*   Displays session success/error messages.
*   Simple form POSTing to `email.test.send`.
*   Includes `@csrf`.
*   Input field for recipient email (`name="email"`) with validation error display.
*   "Send Test Email" submit button.

**Interaction:** User enters email and submits form to trigger sending of a predefined test email via the backend.

*(Further view analysis pending...)*

### Test Email Template (`test.blade.php`)

**File:** `resources/views/emails/test.blade.php`

**Purpose:** Defines the HTML structure and content for the test email sent via the `TestMail` Mailable.

**Layout:** Standalone HTML file with basic inline CSS. Does not use standard Laravel email components/markdown.

**Data:** Uses `date('Y')`.

**Structure:**
*   Simple centered container.
*   Static heading and text confirming email configuration (mentioning SES) is working.
*   Basic footer with copyright.

**Interaction:** Static email content.

*(Further view analysis pending...)*

### Jetstream Team Invitation Email (`team-invitation.blade.php`)

**File:** `resources/views/emails/team-invitation.blade.php`

**Purpose:** Standard Jetstream email template sent when a user is invited to a team.

**Layout:** Uses Laravel Markdown Mail components (`mail::message`, `mail::button`).

**Data:** Expects `$invitation` (Jetstream object) and `$acceptUrl`. Uses Fortify features check.

**Structure:**
*   States which team the user is invited to.
*   Conditionally includes a "Create Account" button if registration is enabled.
*   Includes an "Accept Invitation" button linking to `$acceptUrl`.
*   Includes discard instructions.

**Interaction:** Provides buttons to create account/accept invitation.

*(Further view analysis pending...)*

### Payment Receipt Email (`payment/receipt.blade.php`)

**File:** `resources/views/emails/payment/receipt.blade.php`

**Purpose:** Email template sent to both project owner and pitch creator upon successful payment or project completion (for free projects).

**Layout:** Standalone HTML file with basic inline CSS.

**Data:** Expects `$isFreeProject`, `$recipientName`, `$recipientType`, `$project`, `$pitch`, `$paymentDate`, `$invoiceId`, `$amount`.

**Structure:**
*   Mixpitch logo and title.
*   Greeting and text confirming payment/completion, tailored to recipient.
*   Summary box: Project Name, Pitch ID, Date, Invoice ID (if applicable), Amount/"Free Project".
*   Concluding message tailored to recipient.
*   Button linking to the web receipt view (`projects.pitches.payment.receipt`).
*   Standard footer.

**Interaction:** Confirmation email with link to web receipt.

*(Further view analysis pending...)*

## Filament Views (`resources/views/filament/`)

**Purpose:** Contains Blade views used by the Filament Admin Panel.

**Structure:** Organized into subdirectories like `pages`, `resources`, `widgets`, etc., mirroring Filament concepts.

**Analysis:** Views generally follow standard Filament patterns:
*   Use Filament layout components (e.g., `<x-filament::page>`).
*   Render widgets using `<x-filament-widgets::widgets />` based on configurations in PHP classes.
*   Embed Livewire components for specific functionality (e.g., `<livewire:email-test-form />` in `test-email-page.blade.php`).
*   Minimal custom Blade logic; complexity resides in corresponding `app/Filament/` PHP classes and embedded Livewire components.

**(Skipping detailed analysis of individual files as they follow standard Filament structure).**

*(Further view analysis pending...)*

## Vendor Views (`resources/views/vendor/`)

This directory is **empty**. No third-party package views have been published for customization.

*(Further view analysis pending...)*

## User Profile Views (`resources/views/user-profile/` & `resources/views/profile/`)

### Public Profile View (`user-profile/show.blade.php`)

**File:** `resources/views/user-profile/show.blade.php`

**Purpose:** Displays the public profile page for a given user.

**Layout:** Uses `x-app-layout`.

**Data:** Expects `$user`, `$canEdit` (boolean), `$projects` (collection), `$completedPitches` (collection).

**Structure:**
*   **Profile Header:** Avatar, Name, Username, Headline, Location, Website. Conditional "Edit Profile" button. Social media links, Tipjar link.
*   **Main Content Grid (2 columns):**
    *   **Left:** About (Bio, conditional Tipjar button), Skills/Equipment/Specialties (displayed as badges).
    *   **Right:** User's public Projects grid, Completed Pitches list.

**Interaction:** Displays profile info, projects, completed work. Includes external links (social, website, tipjar) and edit link for owner.

*(Further view analysis pending...)*

### Profile Edit View (Livewire) (`user-profile/edit-livewire.blade.php`)

**File:** `resources/views/user-profile/edit-livewire.blade.php`

**Purpose:** Container view that loads the `UserProfileEdit` Livewire component for editing the authenticated user's profile.

**Layout:** Uses `x-app-layout`.

**Data:** None passed directly; Livewire component fetches authenticated user.

**Structure:** Includes `<livewire:user-profile-edit />`.

**Interaction:** All logic handled by the embedded `UserProfileEdit` Livewire component.

*(Further view analysis pending...)*

### Profile Edit View (Standard Form - Potentially Redundant) (`user-profile/edit.blade.php`)

**File:** `resources/views/user-profile/edit.blade.php`

**Note:** This appears to be a standard HTML form implementation for profile editing, likely redundant due to the Livewire version (`user-profile/edit-livewire.blade.php` loading `UserProfileEdit`).

**Purpose:** Allows authenticated user to edit profile details via a standard form.

**Layout:** Uses `x-app-layout` with "Edit Profile" header and conditional public profile link.

**Data:** Expects `$user`. Uses `session`, `@error`, `old()`.

**Structure:**
*   Displays session messages and username setup prompt.
*   Standard HTML form POSTing to `profile.update` (PUT method).
*   Inputs for Username, Bio, Website, Location.
*   Inputs for Social Links (Twitter, Instagram, SoundCloud, Spotify, YouTube) using array format (`social_links[platform]`). Attempts to use `$this->getSocialUsername()` for pre-filling, suggesting it might have been intended for a Livewire component or requires a helper method.
*   "Save" button.

**Interaction:** Standard HTML form submission to `UserProfileController::update`.

**Redundancy:** Likely redundant/unused in favor of the Livewire implementation.

*(Further view analysis pending...)*

### Jetstream Profile Views (`resources/views/profile/`)

**Purpose:** Contains standard Jetstream views for user profile management (update info, password, 2FA, browser sessions, delete account).

**Structure:** The main view (`show.blade.php`) uses `x-app-layout` and conditionally includes various `@livewire('profile.*')` components based on enabled Jetstream/Fortify features. Other files in the directory are the Blade views for these embedded Livewire components.

**Interaction:** All logic is handled by the embedded Jetstream Livewire components.

**(Skipping detailed analysis of individual files as they are standard Jetstream components).**

*(Further view analysis pending...)*

## Pitch Files Views (`resources/views/pitch-files/`)

### Pitch File Show View (`show.blade.php`)

**File:** `resources/views/pitch-files/show.blade.php`

**Purpose:** Container view to display a single pitch file using the `PitchFilePlayer` Livewire component.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$file` (PitchFile model).

**Structure:**
*   Includes `<livewire:pitch-file-player :file="$file" />`.
*   Pushes basic CSS for waveform loading/timeline.
*   Pushes Wavesurfer.js library.

**Interaction:** All logic handled by the embedded `PitchFilePlayer` Livewire component.

*(Further view analysis pending...)*

## Authentication Views (`resources/views/auth/`)

**Purpose:** Contains standard Laravel Fortify views for handling authentication flows (login, registration, password reset, two-factor auth, etc.).

**Structure:** Views use `x-guest-layout` and standard Jetstream/Fortify Blade components (e.g., `x-authentication-card`). They correspond to Fortify's backend routes and actions.

**Interaction:** Standard HTML forms submitting to Fortify controllers/actions.

**(Skipping detailed analysis of individual files as they are standard Fortify views).**

*(Further view analysis pending...)*

## API Token Views (`resources/views/api/`)

**Purpose:** Contains standard Jetstream views for API token management.

**Structure:** The main view (`index.blade.php`) uses `x-app-layout` and includes the `@livewire('api.api-token-manager')` component. The other file (`api-token-manager.blade.php`) is the view for that Livewire component.

**Interaction:** All logic handled by the embedded Jetstream Livewire component.

**(Skipping detailed analysis as they are standard Jetstream components).**

*(Further view analysis pending...)*

## Projects Views (`resources/views/projects/`)

### Public Project Show View (`project.blade.php`)

**File:** `resources/views/projects/project.blade.php`

**Purpose:** Displays the public detail page for a single project.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project` (with relations), `$canPitch` (boolean), `$userPitch` (Pitch model or null). Uses `Auth`.

**Structure:**
*   **Header Card:** Project image (Alpine lightbox), preview track audio player (Livewire), Project Name, Artist, Owner (`x-user-link`), Status (`x-project-status-button`), Type, Deadline. Conditional buttons (Manage Project / Start Pitch / View Pitch).
*   **Details Section:** Collaboration badges, Budget, Description, Notes.

**Interaction:** Displays project details. Conditional actions for owner (Manage) or others (Start/View Pitch). Pitch initiation uses JS modal (`openPitchTermsModal()`). Includes audio player and image lightbox.

*(Further view analysis pending...)*

### Project Edit View (Standard Form - Potentially Redundant) (`edit.blade.php`)

**File:** `resources/views/projects/edit.blade.php`

**Note:** Likely redundant due to the `ManageProject` Livewire component handling project editing and file management.

**Purpose:** Allows editing project details and managing files via standard HTML forms.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project` (with relations). Uses `old()`.

**Structure:**
*   **Header:** Displays project image (clickable for upload via JS), inputs for Name, Description, Genre, Status.
*   **Form:** Standard HTML form POSTing to `projects.update` (PUT method), `enctype="multipart/form-data"`.
*   **File Management:** Lists existing files with Delete buttons (individual DELETE forms triggered by JS). Links to a separate `projects.createStep2` route for uploads.

**JavaScript:** Includes `loadFile` for image preview and event listener for delete buttons.

**Interaction:** Standard form submission for edits. File deletion via separate forms triggered by JS. Links to potentially deprecated upload step.

**Redundancy:** Likely redundant/unused in favor of the `ManageProject` Livewire implementation.

### Project Index View (`index.blade.php` - Livewire Wrapper)

**File:** `resources/views/projects/index.blade.php`

**Purpose:** Container view for the public project listing.

**Layout:** Extends `components.layouts.app`.

**Structure:** Includes `<livewire:projects-component />`. Assumes the listing logic is handled by this Livewire component.

### Project Upload (Step 2) View (`create_step2.blade.php` - Likely Deprecated)

**File:** `resources/views/projects/create_step2.blade.php`

**Purpose:** Provides a Dropzone.js interface for uploading files as part of a multi-step project creation/edit process.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project`.

**Structure:** Includes Dropzone CSS/JS. Contains Dropzone form POSTing to `projects.storeStep2`. Includes Back/Finish links.

**JavaScript:** Initializes Dropzone.

**Redundancy:** Uses Dropzone and separate upload step, contrasting with integrated S3 uploads in `ManageProject`. Highly likely deprecated.

### Upload Project View (`upload-project.blade.php` - TrackController related)

**File:** `resources/views/projects/upload-project.blade.php`

**Purpose:** Container view, likely related to the `TrackController` functionality.

**Layout:** Extends `components.layouts.app`.

**Structure:** Includes `<livewire:upload-project-component />`. Logic is assumed to be in this Livewire component.

*(Further view analysis pending...)*

## Components (`resources/views/components/`)

### Invoice Details Component (`invoice-details.blade.php`)

**File:** `resources/views/components/invoice-details.blade.php`

**Purpose:** Renders a detailed view of a single invoice, likely using data fetched from Stripe.

**Usage:** Expected to be used like `<x-invoice-details :invoice="$invoiceObject" :view-all-url="route(...)" />`

**Input Props:**
*   `$invoice`: An object containing invoice data (ID/number, status, date, total, description, line items). Structure suggests compatibility with Stripe API responses or formatted objects from `InvoiceService`.
*   `$viewAllUrl` (optional): A URL string for a 'View all invoices' link.

**Key Features:**
*   Displays invoice number, paid/unpaid status badge, date, and total amount.
*   Lists line items from `$invoice->lines->data` or shows a single summary line.
*   Provides a 'Download PDF' link to `route('billing.invoice.download', $invoice->id)`.
*   Optionally provides a 'View all invoices' link.
*   Styled with Tailwind CSS.

**Interaction:** Primarily a display component formatting invoice data. Provides navigation links for downloading and viewing other invoices.

*(Further view analysis pending...)*

### Update Pitch Status Buttons Component (`update-pitch-status.blade.php`)

**File:** `resources/views/components/update-pitch-status.blade.php`

**Purpose:** Renders action buttons for changing a pitch's status based on its current state.
**Note: This component appears highly problematic and likely redundant/deprecated.** Its logic seems to belong within the `UpdatePitchStatus` Livewire component's view (`resources/views/livewire/pitch/component/update-pitch-status.blade.php`). This Blade component bypasses the Livewire component's actions and service layer calls in many cases.

**Usage:** Likely intended usage `<x-update-pitch-status :pitch="$pitch" />`

**Input Props:**
*   `$pitch`: The `Pitch` model.

**Key Features & Issues:**
*   **Conditional Logic:** Uses extensive `@if/@elseif` based on `$pitch->status` to determine which actions/buttons to show.
*   **Direct Form Submissions (Issue):** For several statuses (Pending, In Progress, Approved, Denied, Revisions Requested, Completed), it renders HTML forms that POST directly to controller routes (`projects.pitches.change-status`, `projects.pitches.return-to-approved`). This bypasses the `UpdatePitchStatus` Livewire component and the `PitchWorkflowService`, potentially leading to inconsistent state management and authorization issues.
*   **JavaScript Modal Triggers (Partial Alignment):** For the `READY_FOR_REVIEW` status, it renders buttons that trigger JavaScript functions (`openApproveModal`, etc.) presumably defined elsewhere to interact with modals (`<x-pitch-action-modals />`). This aligns partially with the Livewire component's modal interaction but still passes controller routes directly to JS instead of using Livewire actions.
*   **Includes Modals:** Includes `<x-pitch-action-modals />`.

**Interaction:** Renders conditional buttons/forms. Interacts via direct form POSTs or JavaScript calls, largely bypassing the corresponding Livewire component. Includes a modal component.

**Recommendation:** This component should be reviewed and likely removed. Its logic needs to be consolidated within the `UpdatePitchStatus` Livewire component and its associated view to ensure consistent use of the `PitchWorkflowService` and proper state management.

*(Further view analysis pending...)*

### Pitch Action Modals Component (`pitch-action-modals.blade.php`)

**File:** `resources/views/components/pitch-action-modals.blade.php`

**Purpose:** Defines the HTML structure for modals used for Approve, Deny, and Request Revisions actions on pitches.

**Usage:** Included by other components, e.g., `<x-pitch-action-modals />` (as seen in `update-pitch-status.blade.php`).

**Input Props:** None.

**Key Features:**
*   **Structure:** Contains three distinct modal sections (`#approveModal`, `#denyModal`, `#revisionsModal`), initially hidden.
*   **Forms:** Each modal contains a basic, hidden HTML form (`#approveForm`, etc.) with only `@csrf`. JavaScript is expected to set the `action` URL and potentially add data before submission.
*   **Inputs:** Provides textareas for denial reason (`#denyReason`) and revision requests (`#revisionsRequested`).
*   **Buttons:** Includes Cancel buttons (`onclick="closeModal(...)"`) and confirmation buttons (`#approveSubmitBtn`, etc.) presumably handled by JavaScript.
*   **JavaScript Dependency:** Includes `<script src="{{ asset('js/pitch-modals.js') }}"></script>`, indicating all modal interaction logic (opening, closing, submitting the hidden forms) resides in this external JS file.
*   **Styling:** Uses Tailwind CSS.

**Interaction:** Provides the static modal HTML. Relies entirely on `pitch-modals.js` (and functions like `openApproveModal`, `closeModal`) for display logic and form submission, targeting controller routes directly. This approach is less integrated with Livewire/Alpine compared to using Livewire components for modal state and actions.

**Note:** The reliance on external JS to manipulate and submit hidden forms directly to controller routes is part of the potentially problematic pattern identified in `components/update-pitch-status.blade.php`.

*(Further view analysis pending...)*

### Pitch Terms Modal Component (`pitch-terms-modal.blade.php`)

**File:** `resources/views/components/pitch-terms-modal.blade.php`

**Purpose:** Displays a modal with terms highlights that must be agreed to before initiating a pitch creation request.

**Usage:** Likely included on project detail pages, e.g., `<x-pitch-terms-modal :project="$project" />`. Triggered via JavaScript (`openPitchTermsModal()`).

**Input Props:**
*   `$project`: The `Project` model the user wants to pitch for.

**Key Features:**
*   **Structure:** Defines a modal (`#pitch-terms-modal`) with header, body (terms highlights, full terms link, agreement checkbox), and footer.
*   **Form:** Contains a hidden HTML form (`#pitch-create-form`) targeting `route('projects.pitches.store')` via POST. Includes `@csrf` and `$project->id`.
*   **Agreement:** Requires user to check an 'Agree to Terms' checkbox (`#agree_terms`).
*   **JavaScript:** Includes inline JS defining global functions (`openPitchTermsModal`, `closePitchTermsModal`, `submitPitchForm`) for modal control and form submission. `submitPitchForm` validates checkbox before submitting.
*   **Styling:** Uses Tailwind CSS.

**Interaction:** Provides the UI flow for agreeing to terms before creating a pitch. Uses inline JavaScript for modal display and conditional form submission. Submits directly to a controller route upon confirmation.

*(Further view analysis pending...)*

### User Link Component (`user-link.blade.php`)

**File:** `resources/views/components/user-link.blade.php`

**Purpose:** Renders a user's name, providing a link to their public profile if they have a username.

**Usage:** `<x-user-link :user="$userObject" />`

**Input Props:**
*   `$user`: The `User` model instance.

**Key Features:**
*   Conditionally renders an anchor tag linking to `route('profile.username', ['username' => $user->username])` if `$user->username` is set.
*   Displays the `$user->name` as the link text or plain text.
*   Basic Tailwind link styling.

**Interaction:** Displays user name, conditionally linking to their profile.

*(Further view analysis pending...)*

### Pitch Status Banner Component (`pitch-status.blade.php`)

**File:** `resources/views/components/pitch-status.blade.php`

**Purpose:** Renders a prominent banner displaying a formatted pitch status with dynamic background and text colors.

**Usage:** Likely used via `@include('components.pitch-status', ['status' => $statusString, 'bgColor' => $tailwindBgClass, 'textColor' => $tailwindTextClass])`

**Input Props (Implicit):**
*   `$status`: The pitch status string (e.g., 'ready_for_review').
*   `$bgColor`: A Tailwind background color class (e.g., 'bg-blue-100').
*   `$textColor`: A Tailwind text color class (e.g., 'text-blue-800').

**Key Features:**
*   Formats the `$status` string using `ucwords(str_replace('_', ' ', $status))` for display.
*   Dynamically applies background and text color classes passed via `$bgColor` and `$textColor` using `@apply`.
*   Styled with Tailwind CSS.

**Interaction:** Purely a display component for showing status in a styled banner.

*(Further view analysis pending...)*

### Project Status Button Component (`project-status-button.blade.php`)

**File:** `resources/views/components/project-status-button.blade.php`

**Purpose:** Renders a styled badge or indicator for a project's status, including a relevant icon.

**Usage:** Likely used via `@include('components.project-status-button', ['status' => $project->status, 'type' => 'inline'])` or within Blade component context.

**Input Props (Implicit):**
*   `$status`: The project status string ('unpublished', 'open', 'review', 'completed', 'closed').
*   `$type`: Controls styling variations (e.g., 'top-right' for specific positioning/rounding, defaults to 'inline' styling).

**Key Features:**
*   **Status Mapping:** Uses a PHP `@switch` statement to map project `$status` strings to specific Tailwind CSS classes (`$colorClass`) and Font Awesome icon classes (`$iconClass`).
*   **Custom Colors:** Uses custom color names (e.g., `bg-statusOpen`, `shadow-statusOpen`) presumably defined in Tailwind config or CSS.
*   **Conditional Styling:** Applies different `border-radius` styles based on the `$type` variable.
*   **Display:** Renders a `<span>` containing the icon and the raw `$status` string.
*   **Styling:** Uses Tailwind CSS and Font Awesome.

**Interaction:** Purely a display component for project status indication.

*(Further view analysis pending...)*

### Invoice List View (`invoices.blade.php`)

**File:** `resources/views/billing/invoices.blade.php`

**Purpose:** Displays a paginated list of all the user's invoices.

**Layout:** Uses `x-app-layout` with a header slot ("Billing History") and a back link.

**Data:** Expects an `$invoices` collection (likely paginated Cashier Invoice objects). Uses `session`.

**Structure:**
*   Displays session messages.
*   Shows an empty state if no invoices exist.
*   Displays invoices in a table: Number, Date, Amount, Status (Paid/Unpaid), Type (Pitch Payment/Standard), Actions (View/Download).
*   "Type" column checks metadata for `pitch_id` and links to the project if applicable.
*   Provides "View" and "Download" links per invoice.

**Interaction:** Displays invoice list. Provides links to view/download invoices and navigate to related projects. Assumes pagination if data is paginated.

*(Further view analysis pending...)*

### Billing Diagnostic View (`diagnostic.blade.php`)

**File:** `resources/views/billing/diagnostic.blade.php`

**Purpose:** Developer/admin tool to diagnose invoice synchronization issues by comparing raw Stripe invoice data with Cashier's local count.

**Layout:** Uses `x-app-layout` with a header slot ("Invoice Diagnostic") and a back link.

**Data:** Expects `$stripeId`, `$cashierInvoiceCount`, `$rawInvoiceData` (array of raw Stripe invoices).

**Structure:**
*   Displays Stripe Customer ID, Cashier invoice count, raw Stripe invoice count.
*   Shows a table of raw invoice data fetched directly from Stripe.
*   Provides a list of debugging steps, including suggesting an Artisan command (`stripe:sync-invoices --all`).
*   Includes a "Return to Billing" link.

**Interaction:** Static display of diagnostic information and debugging steps.

*(Further view analysis pending...)*

### Payment Methods Management View (`payment-methods.blade.php`)

**File:** `resources/views/billing/payment-methods.blade.php`

**Purpose:** Allows users to view all saved payment methods, set a default, add new ones, and remove existing ones.

**Layout:** Uses `x-app-layout` with a header slot ("Payment Methods").

**Data:** Expects `$paymentMethods` (collection), `$defaultPaymentMethod`, `$intent` (Stripe SetupIntent). Uses `session`.

**Structure:**
*   Displays session messages.
*   "Add New Card" button toggles a hidden Stripe Elements form.
*   Lists existing payment methods with icon, brand, last 4, expiry.
*   Highlights the default method.
*   Provides "Make Default" button (POST form) for non-default methods.
*   Provides "Remove" button (DELETE form with JS confirm) for all methods.
*   Shows empty state message.
*   Footer links to Billing overview and Stripe Portal.

**JavaScript (`@push('scripts')`):**
*   Includes Stripe.js.
*   Initializes Stripe, mounts Card Element, handles errors.
*   Handles add card form submission using `confirmCardSetup` with SetupIntent, then submits to backend.
*   Toggles add card form visibility.

**Interaction:** Manages multiple payment methods, integrates Stripe Elements for adding cards, uses forms for setting default/removing.

*(Further view analysis pending...)*

### Checkout View (`checkout.blade.php`)

**File:** `resources/views/billing/checkout.blade.php`

**Purpose:** Provides a dedicated page for handling payments, likely for subscriptions or other purchases initiated via Cashier's `checkout`. Focuses solely on collecting payment details.

**Layout:** Uses `x-app-layout` with a header slot ("Checkout").

**Data:** Expects `$intent` (Stripe SetupIntent) and potentially `$priceId`. Uses `session`.

**Structure:**
*   Displays session messages.
*   Contains a single form POSTing to `billing.payment.process`.
*   Includes hidden `price_id` (if provided).
*   Includes Stripe Card Element (`#card-element`) and error display (`#card-errors`).
*   Submit button holds SetupIntent secret.
*   Provides a "Back to Billing" link.

**JavaScript (`@push('scripts')`):**
*   Includes Stripe.js.
*   Initializes Stripe, mounts Card Element, handles errors.
*   Handles form submission using `confirmCardSetup` with SetupIntent, then submits payment method ID and price ID to backend.

**Interaction:** Streamlined checkout page using Stripe Elements and SetupIntents to capture card details for payment processing.

*(Further view analysis pending...)*

### Detailed Invoice View (`invoice-details.blade.php` - Potentially Redundant)

**File:** `resources/views/billing/invoice-details.blade.php`

**Note:** This is a **full page view**, not the component `components/invoice-details.blade.php`. It appears redundant and potentially unused, as `invoice-show.blade.php` uses the component.

**Purpose:** Displays detailed invoice information, including line items, summary, payment method, and billing address.

**Layout:** Uses `x-app-layout` with header including back/download links.

**Data:** Expects `$invoice` (Cashier Invoice object). Uses `session`.

**Structure:**
*   Displays session messages.
*   Shows Invoice Header (Number, Date, Status).
*   Shows Summary (Subtotal, Tax, Discount, Total).
*   Shows Line Items table.
*   **Contains logic within the view** to fetch related Stripe PaymentMethod (for paid invoices) and Customer objects using the Stripe SDK.
*   Displays Payment Method details (Card Brand, Last 4, Expiry).
*   Displays Billing Details (Name, Email, Address).

**Interaction:** Static display of detailed invoice info. Includes logic within the view to fetch related Stripe data.

**Potential Issues:**
*   Likely redundant due to `invoice-show.blade.php` using the `x-invoice-details` component.
*   Contains business logic (Stripe SDK calls) within the view, violating separation of concerns.

*(Further view analysis pending...)*

## Email Views (`resources/views/emails/`)

### Email Test Form View (`test-form.blade.php`)

**File:** `resources/views/emails/test-form.blade.php`

**Purpose:** Provides a simple form for sending a test email (likely corresponds to `EmailController::showTestForm`).

**Layout:** Uses `x-app-layout` with a "Test Email" header.

**Data:** Uses `session` and `@error` directive.

**Structure:**
*   Displays session success/error messages.
*   Simple form POSTing to `email.test.send`.
*   Includes `@csrf`.
*   Input field for recipient email (`name="email"`) with validation error display.
*   "Send Test Email" submit button.

**Interaction:** User enters email and submits form to trigger sending of a predefined test email via the backend.

*(Further view analysis pending...)*

### Test Email Template (`test.blade.php`)

**File:** `resources/views/emails/test.blade.php`

**Purpose:** Defines the HTML structure and content for the test email sent via the `TestMail` Mailable.

**Layout:** Standalone HTML file with basic inline CSS. Does not use standard Laravel email components/markdown.

**Data:** Uses `date('Y')`.

**Structure:**
*   Simple centered container.
*   Static heading and text confirming email configuration (mentioning SES) is working.
*   Basic footer with copyright.

**Interaction:** Static email content.

*(Further view analysis pending...)*

### Jetstream Team Invitation Email (`team-invitation.blade.php`)

**File:** `resources/views/emails/team-invitation.blade.php`

**Purpose:** Standard Jetstream email template sent when a user is invited to a team.

**Layout:** Uses Laravel Markdown Mail components (`mail::message`, `mail::button`).

**Data:** Expects `$invitation` (Jetstream object) and `$acceptUrl`. Uses Fortify features check.

**Structure:**
*   States which team the user is invited to.
*   Conditionally includes a "Create Account" button if registration is enabled.
*   Includes an "Accept Invitation" button linking to `$acceptUrl`.
*   Includes discard instructions.

**Interaction:** Provides buttons to create account/accept invitation.

*(Further view analysis pending...)*

### Payment Receipt Email (`payment/receipt.blade.php`)

**File:** `resources/views/emails/payment/receipt.blade.php`

**Purpose:** Email template sent to both project owner and pitch creator upon successful payment or project completion (for free projects).

**Layout:** Standalone HTML file with basic inline CSS.

**Data:** Expects `$isFreeProject`, `$recipientName`, `$recipientType`, `$project`, `$pitch`, `$paymentDate`, `$invoiceId`, `$amount`.

**Structure:**
*   Mixpitch logo and title.
*   Greeting and text confirming payment/completion, tailored to recipient.
*   Summary box: Project Name, Pitch ID, Date, Invoice ID (if applicable), Amount/"Free Project".
*   Concluding message tailored to recipient.
*   Button linking to the web receipt view (`projects.pitches.payment.receipt`).
*   Standard footer.

**Interaction:** Confirmation email with link to web receipt.

*(Further view analysis pending...)*

## Filament Views (`resources/views/filament/`)

**Purpose:** Contains Blade views used by the Filament Admin Panel.

**Structure:** Organized into subdirectories like `pages`, `resources`, `widgets`, etc., mirroring Filament concepts.

**Analysis:** Views generally follow standard Filament patterns:
*   Use Filament layout components (e.g., `<x-filament::page>`).
*   Render widgets using `<x-filament-widgets::widgets />` based on configurations in PHP classes.
*   Embed Livewire components for specific functionality (e.g., `<livewire:email-test-form />` in `test-email-page.blade.php`).
*   Minimal custom Blade logic; complexity resides in corresponding `app/Filament/` PHP classes and embedded Livewire components.

**(Skipping detailed analysis of individual files as they follow standard Filament structure).**

*(Further view analysis pending...)*

## Vendor Views (`resources/views/vendor/`)

This directory is **empty**. No third-party package views have been published for customization.

*(Further view analysis pending...)*

## User Profile Views (`resources/views/user-profile/` & `resources/views/profile/`)

### Public Profile View (`user-profile/show.blade.php`)

**File:** `resources/views/user-profile/show.blade.php`

**Purpose:** Displays the public profile page for a given user.

**Layout:** Uses `x-app-layout`.

**Data:** Expects `$user`, `$canEdit` (boolean), `$projects` (collection), `$completedPitches` (collection).

**Structure:**
*   **Profile Header:** Avatar, Name, Username, Headline, Location, Website. Conditional "Edit Profile" button. Social media links, Tipjar link.
*   **Main Content Grid (2 columns):**
    *   **Left:** About (Bio, conditional Tipjar button), Skills/Equipment/Specialties (displayed as badges).
    *   **Right:** User's public Projects grid, Completed Pitches list.

**Interaction:** Displays profile info, projects, completed work. Includes external links (social, website, tipjar) and edit link for owner.

*(Further view analysis pending...)*

### Profile Edit View (Livewire) (`user-profile/edit-livewire.blade.php`)

**File:** `resources/views/user-profile/edit-livewire.blade.php`

**Purpose:** Container view that loads the `UserProfileEdit` Livewire component for editing the authenticated user's profile.

**Layout:** Uses `x-app-layout`.

**Data:** None passed directly; Livewire component fetches authenticated user.

**Structure:** Includes `<livewire:user-profile-edit />`.

**Interaction:** All logic handled by the embedded `UserProfileEdit` Livewire component.

*(Further view analysis pending...)*

### Profile Edit View (Standard Form - Potentially Redundant) (`user-profile/edit.blade.php`)

**File:** `resources/views/user-profile/edit.blade.php`

**Note:** This appears to be a standard HTML form implementation for profile editing, likely redundant due to the Livewire version (`user-profile/edit-livewire.blade.php` loading `UserProfileEdit`).

**Purpose:** Allows authenticated user to edit profile details via a standard form.

**Layout:** Uses `x-app-layout` with "Edit Profile" header and conditional public profile link.

**Data:** Expects `$user`. Uses `session`, `@error`, `old()`.

**Structure:**
*   Displays session messages and username setup prompt.
*   Standard HTML form POSTing to `profile.update` (PUT method).
*   Inputs for Username, Bio, Website, Location.
*   Inputs for Social Links (Twitter, Instagram, SoundCloud, Spotify, YouTube) using array format (`social_links[platform]`). Attempts to use `$this->getSocialUsername()` for pre-filling, suggesting it might have been intended for a Livewire component or requires a helper method.
*   "Save" button.

**Interaction:** Standard HTML form submission to `UserProfileController::update`.

**Redundancy:** Likely redundant/unused in favor of the Livewire implementation.

*(Further view analysis pending...)*

### Jetstream Profile Views (`resources/views/profile/`)

**Purpose:** Contains standard Jetstream views for user profile management (update info, password, 2FA, browser sessions, delete account).

**Structure:** The main view (`show.blade.php`) uses `x-app-layout` and conditionally includes various `@livewire('profile.*')` components based on enabled Jetstream/Fortify features. Other files in the directory are the Blade views for these embedded Livewire components.

**Interaction:** All logic is handled by the embedded Jetstream Livewire components.

**(Skipping detailed analysis of individual files as they are standard Jetstream components).**

*(Further view analysis pending...)*

## Pitch Files Views (`resources/views/pitch-files/`)

### Pitch File Show View (`show.blade.php`)

**File:** `resources/views/pitch-files/show.blade.php`

**Purpose:** Container view to display a single pitch file using the `PitchFilePlayer` Livewire component.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$file` (PitchFile model).

**Structure:**
*   Includes `<livewire:pitch-file-player :file="$file" />`.
*   Pushes basic CSS for waveform loading/timeline.
*   Pushes Wavesurfer.js library.

**Interaction:** All logic handled by the embedded `PitchFilePlayer` Livewire component.

*(Further view analysis pending...)*

## Authentication Views (`resources/views/auth/`)

**Purpose:** Contains standard Laravel Fortify views for handling authentication flows (login, registration, password reset, two-factor auth, etc.).

**Structure:** Views use `x-guest-layout` and standard Jetstream/Fortify Blade components (e.g., `x-authentication-card`). They correspond to Fortify's backend routes and actions.

**Interaction:** Standard HTML forms submitting to Fortify controllers/actions.

**(Skipping detailed analysis of individual files as they are standard Fortify views).**

*(Further view analysis pending...)*

## API Token Views (`resources/views/api/`)

**Purpose:** Contains standard Jetstream views for API token management.

**Structure:** The main view (`index.blade.php`) uses `x-app-layout` and includes the `@livewire('api.api-token-manager')` component. The other file (`api-token-manager.blade.php`) is the view for that Livewire component.

**Interaction:** All logic handled by the embedded Jetstream Livewire component.

**(Skipping detailed analysis as they are standard Jetstream components).**

*(Further view analysis pending...)*

## Projects Views (`resources/views/projects/`)

### Public Project Show View (`project.blade.php`)

**File:** `resources/views/projects/project.blade.php`

**Purpose:** Displays the public detail page for a single project.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project` (with relations), `$canPitch` (boolean), `$userPitch` (Pitch model or null). Uses `Auth`.

**Structure:**
*   **Header Card:** Project image (Alpine lightbox), preview track audio player (Livewire), Project Name, Artist, Owner (`x-user-link`), Status (`x-project-status-button`), Type, Deadline. Conditional buttons (Manage Project / Start Pitch / View Pitch).
*   **Details Section:** Collaboration badges, Budget, Description, Notes.

**Interaction:** Displays project details. Conditional actions for owner (Manage) or others (Start/View Pitch). Pitch initiation uses JS modal (`openPitchTermsModal()`). Includes audio player and image lightbox.

*(Further view analysis pending...)*

### Project Edit View (Standard Form - Potentially Redundant) (`edit.blade.php`)

**File:** `resources/views/projects/edit.blade.php`

**Note:** Likely redundant due to the `ManageProject` Livewire component handling project editing and file management.

**Purpose:** Allows editing project details and managing files via standard HTML forms.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project` (with relations). Uses `old()`.

**Structure:**
*   **Header:** Displays project image (clickable for upload via JS), inputs for Name, Description, Genre, Status.
*   **Form:** Standard HTML form POSTing to `projects.update` (PUT method), `enctype="multipart/form-data"`.
*   **File Management:** Lists existing files with Delete buttons (individual DELETE forms triggered by JS). Links to a separate `projects.createStep2` route for uploads.

**JavaScript:** Includes `loadFile` for image preview and event listener for delete buttons.

**Interaction:** Standard form submission for edits. File deletion via separate forms triggered by JS. Links to potentially deprecated upload step.

**Redundancy:** Likely redundant/unused in favor of the `ManageProject` Livewire implementation.

### Project Index View (`index.blade.php` - Livewire Wrapper)

**File:** `resources/views/projects/index.blade.php`

**Purpose:** Container view for the public project listing.

**Layout:** Extends `components.layouts.app`.

**Structure:** Includes `<livewire:projects-component />`. Assumes the listing logic is handled by this Livewire component.

### Project Upload (Step 2) View (`create_step2.blade.php` - Likely Deprecated)

**File:** `resources/views/projects/create_step2.blade.php`

**Purpose:** Provides a Dropzone.js interface for uploading files as part of a multi-step project creation/edit process.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project`.

**Structure:** Includes Dropzone CSS/JS. Contains Dropzone form POSTing to `projects.storeStep2`. Includes Back/Finish links.

**JavaScript:** Initializes Dropzone.

**Redundancy:** Uses Dropzone and separate upload step, contrasting with integrated S3 uploads in `ManageProject`. Highly likely deprecated.

### Upload Project View (`upload-project.blade.php` - TrackController related)

**File:** `resources/views/projects/upload-project.blade.php`

**Purpose:** Container view, likely related to the `TrackController` functionality.

**Layout:** Extends `components.layouts.app`.

**Structure:** Includes `<livewire:upload-project-component />`. Logic is assumed to be in this Livewire component.

*(Further view analysis pending...)*

## Components (`resources/views/components/`)

### Invoice Details Component (`invoice-details.blade.php`)

**File:** `resources/views/components/invoice-details.blade.php`

**Purpose:** Renders a detailed view of a single invoice, likely using data fetched from Stripe.

**Usage:** Expected to be used like `<x-invoice-details :invoice="$invoiceObject" :view-all-url="route(...)" />`

**Input Props:**
*   `$invoice`: An object containing invoice data (ID/number, status, date, total, description, line items). Structure suggests compatibility with Stripe API responses or formatted objects from `InvoiceService`.
*   `$viewAllUrl` (optional): A URL string for a 'View all invoices' link.

**Key Features:**
*   Displays invoice number, paid/unpaid status badge, date, and total amount.
*   Lists line items from `$invoice->lines->data` or shows a single summary line.
*   Provides a 'Download PDF' link to `route('billing.invoice.download', $invoice->id)`.
*   Optionally provides a 'View all invoices' link.
*   Styled with Tailwind CSS.

**Interaction:** Primarily a display component formatting invoice data. Provides navigation links for downloading and viewing other invoices.

*(Further view analysis pending...)*

### Update Pitch Status Buttons Component (`update-pitch-status.blade.php`)

**File:** `resources/views/components/update-pitch-status.blade.php`

**Purpose:** Renders action buttons for changing a pitch's status based on its current state.
**Note: This component appears highly problematic and likely redundant/deprecated.** Its logic seems to belong within the `UpdatePitchStatus` Livewire component's view (`resources/views/livewire/pitch/component/update-pitch-status.blade.php`). This Blade component bypasses the Livewire component's actions and service layer calls in many cases.

**Usage:** Likely intended usage `<x-update-pitch-status :pitch="$pitch" />`

**Input Props:**
*   `$pitch`: The `Pitch` model.

**Key Features & Issues:**
*   **Conditional Logic:** Uses extensive `@if/@elseif` based on `$pitch->status` to determine which actions/buttons to show.
*   **Direct Form Submissions (Issue):** For several statuses (Pending, In Progress, Approved, Denied, Revisions Requested, Completed), it renders HTML forms that POST directly to controller routes (`projects.pitches.change-status`, `projects.pitches.return-to-approved`). This bypasses the `UpdatePitchStatus` Livewire component and the `PitchWorkflowService`, potentially leading to inconsistent state management and authorization issues.
*   **JavaScript Modal Triggers (Partial Alignment):** For the `READY_FOR_REVIEW` status, it renders buttons that trigger JavaScript functions (`openApproveModal`, etc.) presumably defined elsewhere to interact with modals (`<x-pitch-action-modals />`). This aligns partially with the Livewire component's modal interaction but still passes controller routes directly to JS instead of using Livewire actions.
*   **Includes Modals:** Includes `<x-pitch-action-modals />`.

**Interaction:** Renders conditional buttons/forms. Interacts via direct form POSTs or JavaScript calls, largely bypassing the corresponding Livewire component. Includes a modal component.

**Recommendation:** This component should be reviewed and likely removed. Its logic needs to be consolidated within the `UpdatePitchStatus` Livewire component and its associated view to ensure consistent use of the `PitchWorkflowService` and proper state management.

*(Further view analysis pending...)*

### Pitch Action Modals Component (`pitch-action-modals.blade.php`)

**File:** `resources/views/components/pitch-action-modals.blade.php`

**Purpose:** Defines the HTML structure for modals used for Approve, Deny, and Request Revisions actions on pitches.

**Usage:** Included by other components, e.g., `<x-pitch-action-modals />` (as seen in `update-pitch-status.blade.php`).

**Input Props:** None.

**Key Features:**
*   **Structure:** Contains three distinct modal sections (`#approveModal`, `#denyModal`, `#revisionsModal`), initially hidden.
*   **Forms:** Each modal contains a basic, hidden HTML form (`#approveForm`, etc.) with only `@csrf`. JavaScript is expected to set the `action` URL and potentially add data before submission.
*   **Inputs:** Provides textareas for denial reason (`#denyReason`) and revision requests (`#revisionsRequested`).
*   **Buttons:** Includes Cancel buttons (`onclick="closeModal(...)"`) and confirmation buttons (`#approveSubmitBtn`, etc.) presumably handled by JavaScript.
*   **JavaScript Dependency:** Includes `<script src="{{ asset('js/pitch-modals.js') }}"></script>`, indicating all modal interaction logic (opening, closing, submitting the hidden forms) resides in this external JS file.
*   **Styling:** Uses Tailwind CSS.

**Interaction:** Provides the static modal HTML. Relies entirely on `pitch-modals.js` (and functions like `openApproveModal`, `closeModal`) for display logic and form submission, targeting controller routes directly. This approach is less integrated with Livewire/Alpine compared to using Livewire components for modal state and actions.

**Note:** The reliance on external JS to manipulate and submit hidden forms directly to controller routes is part of the potentially problematic pattern identified in `components/update-pitch-status.blade.php`.

*(Further view analysis pending...)*

### Pitch Terms Modal Component (`pitch-terms-modal.blade.php`)

**File:** `resources/views/components/pitch-terms-modal.blade.php`

**Purpose:** Displays a modal with terms highlights that must be agreed to before initiating a pitch creation request.

**Usage:** Likely included on project detail pages, e.g., `<x-pitch-terms-modal :project="$project" />`. Triggered via JavaScript (`openPitchTermsModal()`).

**Input Props:**
*   `$project`: The `Project` model the user wants to pitch for.

**Key Features:**
*   **Structure:** Defines a modal (`#pitch-terms-modal`) with header, body (terms highlights, full terms link, agreement checkbox), and footer.
*   **Form:** Contains a hidden HTML form (`#pitch-create-form`) targeting `route('projects.pitches.store')` via POST. Includes `@csrf` and `$project->id`.
*   **Agreement:** Requires user to check an 'Agree to Terms' checkbox (`#agree_terms`).
*   **JavaScript:** Includes inline JS defining global functions (`openPitchTermsModal`, `closePitchTermsModal`, `submitPitchForm`) for modal control and form submission. `submitPitchForm` validates checkbox before submitting.
*   **Styling:** Uses Tailwind CSS.

**Interaction:** Provides the UI flow for agreeing to terms before creating a pitch. Uses inline JavaScript for modal display and conditional form submission. Submits directly to a controller route upon confirmation.

*(Further view analysis pending...)*

### User Link Component (`user-link.blade.php`)

**File:** `resources/views/components/user-link.blade.php`

**Purpose:** Renders a user's name, providing a link to their public profile if they have a username.

**Usage:** `<x-user-link :user="$userObject" />`

**Input Props:**
*   `$user`: The `User` model instance.

**Key Features:**
*   Conditionally renders an anchor tag linking to `route('profile.username', ['username' => $user->username])` if `$user->username` is set.
*   Displays the `$user->name` as the link text or plain text.
*   Basic Tailwind link styling.

**Interaction:** Displays user name, conditionally linking to their profile.

*(Further view analysis pending...)*

### Pitch Status Banner Component (`pitch-status.blade.php`)

**File:** `resources/views/components/pitch-status.blade.php`

**Purpose:** Renders a prominent banner displaying a formatted pitch status with dynamic background and text colors.

**Usage:** Likely used via `@include('components.pitch-status', ['status' => $statusString, 'bgColor' => $tailwindBgClass, 'textColor' => $tailwindTextClass])`

**Input Props (Implicit):**
*   `$status`: The pitch status string (e.g., 'ready_for_review').
*   `$bgColor`: A Tailwind background color class (e.g., 'bg-blue-100').
*   `$textColor`: A Tailwind text color class (e.g., 'text-blue-800').

**Key Features:**
*   Formats the `$status` string using `ucwords(str_replace('_', ' ', $status))` for display.
*   Dynamically applies background and text color classes passed via `$bgColor` and `$textColor` using `@apply`.
*   Styled with Tailwind CSS.

**Interaction:** Purely a display component for showing status in a styled banner.

*(Further view analysis pending...)*

### Project Status Button Component (`project-status-button.blade.php`)

**File:** `resources/views/components/project-status-button.blade.php`

**Purpose:** Renders a styled badge or indicator for a project's status, including a relevant icon.

**Usage:** Likely used via `@include('components.project-status-button', ['status' => $project->status, 'type' => 'inline'])` or within Blade component context.

**Input Props (Implicit):**
*   `$status`: The project status string ('unpublished', 'open', 'review', 'completed', 'closed').
*   `$type`: Controls styling variations (e.g., 'top-right' for specific positioning/rounding, defaults to 'inline' styling).

**Key Features:**
*   **Status Mapping:** Uses a PHP `@switch` statement to map project `$status` strings to specific Tailwind CSS classes (`$colorClass`) and Font Awesome icon classes (`$iconClass`).
*   **Custom Colors:** Uses custom color names (e.g., `bg-statusOpen`, `shadow-statusOpen`) presumably defined in Tailwind config or CSS.
*   **Conditional Styling:** Applies different `border-radius` styles based on the `$type` variable.
*   **Display:** Renders a `<span>` containing the icon and the raw `$status` string.
*   **Styling:** Uses Tailwind CSS and Font Awesome.

**Interaction:** Purely a display component for project status indication.

*(Further view analysis pending...)*

### Invoice List View (`invoices.blade.php`)

**File:** `resources/views/billing/invoices.blade.php`

**Purpose:** Displays a paginated list of all the user's invoices.

**Layout:** Uses `x-app-layout` with a header slot ("Billing History") and a back link.

**Data:** Expects an `$invoices` collection (likely paginated Cashier Invoice objects). Uses `session`.

**Structure:**
*   Displays session messages.
*   Shows an empty state if no invoices exist.
*   Displays invoices in a table: Number, Date, Amount, Status (Paid/Unpaid), Type (Pitch Payment/Standard), Actions (View/Download).
*   "Type" column checks metadata for `pitch_id` and links to the project if applicable.
*   Provides "View" and "Download" links per invoice.

**Interaction:** Displays invoice list. Provides links to view/download invoices and navigate to related projects. Assumes pagination if data is paginated.

*(Further view analysis pending...)*

### Billing Diagnostic View (`diagnostic.blade.php`)

**File:** `resources/views/billing/diagnostic.blade.php`

**Purpose:** Developer/admin tool to diagnose invoice synchronization issues by comparing raw Stripe invoice data with Cashier's local count.

**Layout:** Uses `x-app-layout` with a header slot ("Invoice Diagnostic") and a back link.

**Data:** Expects `$stripeId`, `$cashierInvoiceCount`, `$rawInvoiceData` (array of raw Stripe invoices).

**Structure:**
*   Displays Stripe Customer ID, Cashier invoice count, raw Stripe invoice count.
*   Shows a table of raw invoice data fetched directly from Stripe.
*   Provides a list of debugging steps, including suggesting an Artisan command (`stripe:sync-invoices --all`).
*   Includes a "Return to Billing" link.

**Interaction:** Static display of diagnostic information and debugging steps.

*(Further view analysis pending...)*

### Payment Methods Management View (`payment-methods.blade.php`)

**File:** `resources/views/billing/payment-methods.blade.php`

**Purpose:** Allows users to view all saved payment methods, set a default, add new ones, and remove existing ones.

**Layout:** Uses `x-app-layout` with a header slot ("Payment Methods").

**Data:** Expects `$paymentMethods` (collection), `$defaultPaymentMethod`, `$intent` (Stripe SetupIntent). Uses `session`.

**Structure:**
*   Displays session messages.
*   "Add New Card" button toggles a hidden Stripe Elements form.
*   Lists existing payment methods with icon, brand, last 4, expiry.
*   Highlights the default method.
*   Provides "Make Default" button (POST form) for non-default methods.
*   Provides "Remove" button (DELETE form with JS confirm) for all methods.
*   Shows empty state message.
*   Footer links to Billing overview and Stripe Portal.

**JavaScript (`@push('scripts')`):**
*   Includes Stripe.js.
*   Initializes Stripe, mounts Card Element, handles errors.
*   Handles add card form submission using `confirmCardSetup` with SetupIntent, then submits to backend.
*   Toggles add card form visibility.

**Interaction:** Manages multiple payment methods, integrates Stripe Elements for adding cards, uses forms for setting default/removing.

*(Further view analysis pending...)*

### Checkout View (`checkout.blade.php`)

**File:** `resources/views/billing/checkout.blade.php`

**Purpose:** Provides a dedicated page for handling payments, likely for subscriptions or other purchases initiated via Cashier's `checkout`. Focuses solely on collecting payment details.

**Layout:** Uses `x-app-layout` with a header slot ("Checkout").

**Data:** Expects `$intent` (Stripe SetupIntent) and potentially `$priceId`. Uses `session`.

**Structure:**
*   Displays session messages.
*   Contains a single form POSTing to `billing.payment.process`.
*   Includes hidden `price_id` (if provided).
*   Includes Stripe Card Element (`#card-element`) and error display (`#card-errors`).
*   Submit button holds SetupIntent secret.
*   Provides a "Back to Billing" link.

**JavaScript (`@push('scripts')`):**
*   Includes Stripe.js.
*   Initializes Stripe, mounts Card Element, handles errors.
*   Handles form submission using `confirmCardSetup` with SetupIntent, then submits payment method ID and price ID to backend.

**Interaction:** Streamlined checkout page using Stripe Elements and SetupIntents to capture card details for payment processing.

*(Further view analysis pending...)*

### Detailed Invoice View (`invoice-details.blade.php` - Potentially Redundant)

**File:** `resources/views/billing/invoice-details.blade.php`

**Note:** This is a **full page view**, not the component `components/invoice-details.blade.php`. It appears redundant and potentially unused, as `invoice-show.blade.php` uses the component.

**Purpose:** Displays detailed invoice information, including line items, summary, payment method, and billing address.

**Layout:** Uses `x-app-layout` with header including back/download links.

**Data:** Expects `$invoice` (Cashier Invoice object). Uses `session`.

**Structure:**
*   Displays session messages.
*   Shows Invoice Header (Number, Date, Status).
*   Shows Summary (Subtotal, Tax, Discount, Total).
*   Shows Line Items table.
*   **Contains logic within the view** to fetch related Stripe PaymentMethod (for paid invoices) and Customer objects using the Stripe SDK.
*   Displays Payment Method details (Card Brand, Last 4, Expiry).
*   Displays Billing Details (Name, Email, Address).

**Interaction:** Static display of detailed invoice info. Includes logic within the view to fetch related Stripe data.

**Potential Issues:**
*   Likely redundant due to `invoice-show.blade.php` using the `x-invoice-details` component.
*   Contains business logic (Stripe SDK calls) within the view, violating separation of concerns.

*(Further view analysis pending...)*

## Email Views (`resources/views/emails/`)

### Email Test Form View (`test-form.blade.php`)

**File:** `resources/views/emails/test-form.blade.php`

**Purpose:** Provides a simple form for sending a test email (likely corresponds to `EmailController::showTestForm`).

**Layout:** Uses `x-app-layout` with a "Test Email" header.

**Data:** Uses `session` and `@error` directive.

**Structure:**
*   Displays session success/error messages.
*   Simple form POSTing to `email.test.send`.
*   Includes `@csrf`.
*   Input field for recipient email (`name="email"`) with validation error display.
*   "Send Test Email" submit button.

**Interaction:** User enters email and submits form to trigger sending of a predefined test email via the backend.

*(Further view analysis pending...)*

### Test Email Template (`test.blade.php`)

**File:** `resources/views/emails/test.blade.php`

**Purpose:** Defines the HTML structure and content for the test email sent via the `TestMail` Mailable.

**Layout:** Standalone HTML file with basic inline CSS. Does not use standard Laravel email components/markdown.

**Data:** Uses `date('Y')`.

**Structure:**
*   Simple centered container.
*   Static heading and text confirming email configuration (mentioning SES) is working.
*   Basic footer with copyright.

**Interaction:** Static email content.

*(Further view analysis pending...)*

### Jetstream Team Invitation Email (`team-invitation.blade.php`)

**File:** `resources/views/emails/team-invitation.blade.php`

**Purpose:** Standard Jetstream email template sent when a user is invited to a team.

**Layout:** Uses Laravel Markdown Mail components (`mail::message`, `mail::button`).

**Data:** Expects `$invitation` (Jetstream object) and `$acceptUrl`. Uses Fortify features check.

**Structure:**
*   States which team the user is invited to.
*   Conditionally includes a "Create Account" button if registration is enabled.
*   Includes an "Accept Invitation" button linking to `$acceptUrl`.
*   Includes discard instructions.

**Interaction:** Provides buttons to create account/accept invitation.

*(Further view analysis pending...)*

### Payment Receipt Email (`payment/receipt.blade.php`)

**File:** `resources/views/emails/payment/receipt.blade.php`

**Purpose:** Email template sent to both project owner and pitch creator upon successful payment or project completion (for free projects).

**Layout:** Standalone HTML file with basic inline CSS.

**Data:** Expects `$isFreeProject`, `$recipientName`, `$recipientType`, `$project`, `$pitch`, `$paymentDate`, `$invoiceId`, `$amount`.

**Structure:**
*   Mixpitch logo and title.
*   Greeting and text confirming payment/completion, tailored to recipient.
*   Summary box: Project Name, Pitch ID, Date, Invoice ID (if applicable), Amount/"Free Project".
*   Concluding message tailored to recipient.
*   Button linking to the web receipt view (`projects.pitches.payment.receipt`).
*   Standard footer.

**Interaction:** Confirmation email with link to web receipt.

*(Further view analysis pending...)*

## Filament Views (`resources/views/filament/`)

**Purpose:** Contains Blade views used by the Filament Admin Panel.

**Structure:** Organized into subdirectories like `pages`, `resources`, `widgets`, etc., mirroring Filament concepts.

**Analysis:** Views generally follow standard Filament patterns:
*   Use Filament layout components (e.g., `<x-filament::page>`).
*   Render widgets using `<x-filament-widgets::widgets />` based on configurations in PHP classes.
*   Embed Livewire components for specific functionality (e.g., `<livewire:email-test-form />` in `test-email-page.blade.php`).
*   Minimal custom Blade logic; complexity resides in corresponding `app/Filament/` PHP classes and embedded Livewire components.

**(Skipping detailed analysis of individual files as they follow standard Filament structure).**

*(Further view analysis pending...)*

## Vendor Views (`resources/views/vendor/`)

This directory is **empty**. No third-party package views have been published for customization.

*(Further view analysis pending...)*

## User Profile Views (`resources/views/user-profile/` & `resources/views/profile/`)

### Public Profile View (`user-profile/show.blade.php`)

**File:** `resources/views/user-profile/show.blade.php`

**Purpose:** Displays the public profile page for a given user.

**Layout:** Uses `x-app-layout`.

**Data:** Expects `$user`, `$canEdit` (boolean), `$projects` (collection), `$completedPitches` (collection).

**Structure:**
*   **Profile Header:** Avatar, Name, Username, Headline, Location, Website. Conditional "Edit Profile" button. Social media links, Tipjar link.
*   **Main Content Grid (2 columns):**
    *   **Left:** About (Bio, conditional Tipjar button), Skills/Equipment/Specialties (displayed as badges).
    *   **Right:** User's public Projects grid, Completed Pitches list.

**Interaction:** Displays profile info, projects, completed work. Includes external links (social, website, tipjar) and edit link for owner.

*(Further view analysis pending...)*

### Profile Edit View (Livewire) (`user-profile/edit-livewire.blade.php`)

**File:** `resources/views/user-profile/edit-livewire.blade.php`

**Purpose:** Container view that loads the `UserProfileEdit` Livewire component for editing the authenticated user's profile.

**Layout:** Uses `x-app-layout`.

**Data:** None passed directly; Livewire component fetches authenticated user.

**Structure:** Includes `<livewire:user-profile-edit />`.

**Interaction:** All logic handled by the embedded `UserProfileEdit` Livewire component.

*(Further view analysis pending...)*

### Profile Edit View (Standard Form - Potentially Redundant) (`user-profile/edit.blade.php`)

**File:** `resources/views/user-profile/edit.blade.php`

**Note:** This appears to be a standard HTML form implementation for profile editing, likely redundant due to the Livewire version (`user-profile/edit-livewire.blade.php` loading `UserProfileEdit`).

**Purpose:** Allows authenticated user to edit profile details via a standard form.

**Layout:** Uses `x-app-layout` with "Edit Profile" header and conditional public profile link.

**Data:** Expects `$user`. Uses `session`, `@error`, `old()`.

**Structure:**
*   Displays session messages and username setup prompt.
*   Standard HTML form POSTing to `profile.update` (PUT method).
*   Inputs for Username, Bio, Website, Location.
*   Inputs for Social Links (Twitter, Instagram, SoundCloud, Spotify, YouTube) using array format (`social_links[platform]`). Attempts to use `$this->getSocialUsername()` for pre-filling, suggesting it might have been intended for a Livewire component or requires a helper method.
*   "Save" button.

**Interaction:** Standard HTML form submission to `UserProfileController::update`.

**Redundancy:** Likely redundant/unused in favor of the Livewire implementation.

*(Further view analysis pending...)*

### Jetstream Profile Views (`resources/views/profile/`)

**Purpose:** Contains standard Jetstream views for user profile management (update info, password, 2FA, browser sessions, delete account).

**Structure:** The main view (`show.blade.php`) uses `x-app-layout` and conditionally includes various `@livewire('profile.*')` components based on enabled Jetstream/Fortify features. Other files in the directory are the Blade views for these embedded Livewire components.

**Interaction:** All logic is handled by the embedded Jetstream Livewire components.

**(Skipping detailed analysis of individual files as they are standard Jetstream components).**

*(Further view analysis pending...)*

## Pitch Files Views (`resources/views/pitch-files/`)

### Pitch File Show View (`show.blade.php`)

**File:** `resources/views/pitch-files/show.blade.php`

**Purpose:** Container view to display a single pitch file using the `PitchFilePlayer` Livewire component.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$file` (PitchFile model).

**Structure:**
*   Includes `<livewire:pitch-file-player :file="$file" />`.
*   Pushes basic CSS for waveform loading/timeline.
*   Pushes Wavesurfer.js library.

**Interaction:** All logic handled by the embedded `PitchFilePlayer` Livewire component.

*(Further view analysis pending...)*

## Authentication Views (`resources/views/auth/`)

**Purpose:** Contains standard Laravel Fortify views for handling authentication flows (login, registration, password reset, two-factor auth, etc.).

**Structure:** Views use `x-guest-layout` and standard Jetstream/Fortify Blade components (e.g., `x-authentication-card`). They correspond to Fortify's backend routes and actions.

**Interaction:** Standard HTML forms submitting to Fortify controllers/actions.

**(Skipping detailed analysis of individual files as they are standard Fortify views).**

*(Further view analysis pending...)*

## API Token Views (`resources/views/api/`)

**Purpose:** Contains standard Jetstream views for API token management.

**Structure:** The main view (`index.blade.php`) uses `x-app-layout` and includes the `@livewire('api.api-token-manager')` component. The other file (`api-token-manager.blade.php`) is the view for that Livewire component.

**Interaction:** All logic handled by the embedded Jetstream Livewire component.

**(Skipping detailed analysis as they are standard Jetstream components).**

*(Further view analysis pending...)*

## Projects Views (`resources/views/projects/`)

### Public Project Show View (`project.blade.php`)

**File:** `resources/views/projects/project.blade.php`

**Purpose:** Displays the public detail page for a single project.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project` (with relations), `$canPitch` (boolean), `$userPitch` (Pitch model or null). Uses `Auth`.

**Structure:**
*   **Header Card:** Project image (Alpine lightbox), preview track audio player (Livewire), Project Name, Artist, Owner (`x-user-link`), Status (`x-project-status-button`), Type, Deadline. Conditional buttons (Manage Project / Start Pitch / View Pitch).
*   **Details Section:** Collaboration badges, Budget, Description, Notes.

**Interaction:** Displays project details. Conditional actions for owner (Manage) or others (Start/View Pitch). Pitch initiation uses JS modal (`openPitchTermsModal()`). Includes audio player and image lightbox.

*(Further view analysis pending...)*

### Project Edit View (Standard Form - Potentially Redundant) (`edit.blade.php`)

**File:** `resources/views/projects/edit.blade.php`

**Note:** Likely redundant due to the `ManageProject` Livewire component handling project editing and file management.

**Purpose:** Allows editing project details and managing files via standard HTML forms.

**Layout:** Extends `components.layouts.app`.

**Data:** Expects `$project` (with relations). Uses `old()`.

**Structure:**
*   **Header:** Displays project image (clickable for upload via JS), inputs for Name, Description, Genre, Status.
*   **Form:** Standard HTML form POSTing to `projects.update` (PUT method), `enctype="multipart/form-data"`.
*   **File Management:** Lists existing files with Delete buttons (individual DELETE forms triggered by JS). Links to a separate `projects.createStep2` route for uploads.

**JavaScript:** Includes `loadFile` for image preview and event listener for delete buttons.

**Interaction:** Standard form submission for edits. File deletion via separate forms triggered by JS. Links to potentially deprecated upload step.

**Redundancy:** Likely redundant/unused in favor of the `ManageProject` Livewire implementation.

### Project Index View (`index.blade.php` - Livewire Wrapper)

**Interaction:** Conf