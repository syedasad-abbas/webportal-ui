<a id="readme-top"></a>

[![Contributors][contributors-shield]][contributors-url]
[![Forks][forks-shield]][forks-url]
[![Stargazers][stars-shield]][stars-url]
[![Issues][issues-shield]][issues-url]
[![Unlicense License][license-shield]][license-url]
[![LinkedIn][linkedin-shield]][linkedin-url]

<img width="100%" alt="Lara Dashboard" src="https://github.com/user-attachments/assets/c56009a4-718f-43dc-bd1e-caad5417b05b"  />

**⚡ Lara Dashboard** CMS By Laravel (7.x - 12.x) - Manages Users, Roles, Permissions, Modules, Settings, Translations, Contents(Post, Page, Category, Tags), System logs, Monitoring and every actions of your Laravel application. A complete CMS solution for Laravel application with Tailwind CSS integrated with all starting features including modules, dark/lite mode, charts, tables, forms, lots of components and many more. By our preimium modules, you can get more features like CRM, HRM, Course Management and so on.

**Demo:** https://demo.laradashboard.com/

```
Email - superadmin@example.com
password - 12345678
```

## 📋 Requirements:

-   Spatie role permission package `^6.4`
-   PHPUnit test package `^11.x`
-   Tailwind CSS >= 4.x
-   Laravel Modules - https://laravelmodules.com/docs/12/getting-started/introduction
-   Laravel Events (A WordPress like action/filter hooks) - https://github.com/tormjens/eventy
-   PHP 8.3 or 8.4

<p align="right">(<a href="#readme-top">back to top</a>)</p>

### 🛠️ Built With

-   [![PHP][PHP.com]][PHP-url]
-   [![Laravel][Laravel.com]][Laravel-url]
-   [![Tailwind CSS][TailwindCSS.com]][TailwindCSS-url]
-   [![JavaScript][JavaScript.com]][JavaScript-url]
-   [![Alpine JS][AlpineJS.com]][AlpineJS-url]
-   [![React][React.js]][React-url]
-   [![MySQL][MySQL.com]][MySQL-url]
-   <a href="https://penguinui.com/">
      <img src="https://res.cloudinary.com/ds8pgw1pf/image/upload/v1721401292/penguinui/main-assets/Logo.png" alt="Penguin UI" style="height: 30px;">
     </a>
-   <a href="https://tailadmin.com" style="display: flex; align-items: center; text-decoration: none; color: #3d51e0;">
      <img src="https://avatars.githubusercontent.com/u/95587422?v=4" alt="Tail Admin" style="height: 20px;"> <span style="color:#3d51e0; margin-left: 5px;">Tail Admin</span>
     </a>

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## 📝 Changelog

**[v1.5.0] - 2025-06-01**

-   **Feature**: Content Management System (CMS) with Content(Post/Page), Content Category, Content Tag management.
-   **Feature**: Post/Page activity chart in Dashboard.
-   **Feature**: Bulk delete for Users, Roles, Posts, Categories, Tags.
-   **Enhancement**: Sorting features for Users, Roles, Permissions, Posts, Categories, Tags.
-   **Enhancement**: Components - Confirm delete, Success/Error messages, Toast, Text Editor, Breadcrumbs, Action dropdown.
-   **Fix**: System dark mode issues.
-   **Fix**: Modules refactorring in demo mode.

**[v1.3.0] - 2025-05-18**

-   **Feature**: Admin Menu architecture with more extendible way.
-   **Feature**: Permission List and detail page.
-   **Enhancement**: Improved module compatibility.

**[v1.2.0] - 2025-05-12**

-   **Feature - Translation Management**: Added Translation management sytem with supporting 21 languages by default and possibility to add any in a second.
-   **Enhancement - Dashboard Redesign**: Dashboard redesigned with new card, user history chart, several more design improvements.
-   **Enhancement**: Role list page, user list page to add links of users list sorting by role and role edit page linkings.
-   **Enhancement**: Cleanup code base to use services, requests more, use SOLID whenever needed.
-   **Fix**: Fixed #109 Submenu dropdown icon doesn't change on open/close submenu of a menu item.
-   **Fix**: Fixed #105 Sidebar Icon not working good if collapsed.
-   **Fix**: Fixed #93 Theme primary color, secondary color was not working.
-   **Fix**: Fixed #99 Superadmin role shouldn't be edited.
-   **Fix**: Fixed Mobile responsive has some issues.
-   **Fix**: Fixed Sidebar toggle was not persistent issue.
-   **Fix**: Fixed Role create -> selecting permission group can't check the permissions in that group checkboxes automatically.

**[v1.0.0] - 2025-04-21**

-   **Feature - Forget Password Management**: Enhanced the forget password functionality for better reliability and user experience.
-   **Feature - Settings Management**: Added comprehensive settings management features, including API support.
-   **Enhancement - Role-Based Access Control (RBAC) Improvements**: Improved authorization mechanisms and role-based access control.
-   **Feature - Admin Impersonation**: Administrators can now log in as other users and switch back to their original accounts seamlessly.
-   **Enhancement - UI/UX Enhancements**: Updated the role create/edit form for a more intuitive and user-friendly experience.
-   **Enhancement - User Profile and Management Enhancements**: Refactored user-related operations to utilize `UserService` and `RolesService` for better separation of concerns and maintainability.
-   **Docs - Documentation and Configuration Updates**:
    -   Updated `.env.example` to include a `GITHUB_LINK` variable for improved project visibility.

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## 🔄 Versions:

Latest version `v1.5.0` - https://github.com/ManiruzzamanAkash/laravel-role/releases/tag/v1.5.0

<details>
<summary>View Old versions</summary>

-   Laravel `7.x` & PHP -`7.x`

    -   Tag - https://github.com/ManiruzzamanAkash/laravel-role/releases/tag/Laravel7.x
    -   Branch - https://github.com/ManiruzzamanAkash/laravel-role/tree/Laravel7.x

-   Laravel `9.7` & PHP - `8.x`

    -   Tag - https://github.com/ManiruzzamanAkash/laravel-role/releases/tag/Laravel9.x

-   Laravel `11.x`

    -   Tag - https://github.com/ManiruzzamanAkash/laravel-role/releases/tag/v11.x-main

-   Laravel `12.x` & PHP >= `8.3`

    -   Tag - https://github.com/ManiruzzamanAkash/laravel-role/releases/tag/Laravel12.x

-   Laravel `12.x` & Tail Admin Template Integration

    -   Tag - https://github.com/ManiruzzamanAkash/laravel-role/releases/tag/Laravel12.x-tailadmin

-   Laravel `12.x` & Module & Action Log integration

    -   Tag - https://github.com/ManiruzzamanAkash/laravel-role/releases/tag/Laravel12.x-module-logs

-   v1.0.0 - Settings, Forget password and lots of refactorring
    -   Tag - https://github.com/ManiruzzamanAkash/laravel-role/releases/tag/v1.0.0
-   v1.2.0 - Translation Management, Dashboard Redesign, Role/User List improvements
    -   Tag - https://github.com/ManiruzzamanAkash/laravel-role/releases/tag/v1.2.0
-   v1.3.0 - Admin Menu architecture, Permission List and detail page
    -   Tag - https://github.com/ManiruzzamanAkash/laravel-role/releases/tag/v1.3.0

More release tags - https://github.com/laradashboard/laradashboard/releases

</details>

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## 🚀 Project Setup

**Clone and Go Project**

```console
git clone git@github.com:laradashboard/laradashboard.git
cd laradashboard
```

**Install Composer & Node Dependencies**

```console
composer install
npm install
```

**Database & env creation**

-   Create database called - `laradashboard`
-   Create `.env` file by copying `.env.example` file

**Generate Artisan Key or necessary linkings**

```console
php artisan key:generate
php artisan storage:link
```

**Migrate Database with seeder**

```console
php artisan migrate:fresh --seed && php artisan module:seed
```

**Run Project**

```php
php artisan serve
npm run dev
```

So, You've got the project of Lara Dashboard on your local machine - http://localhost:8000

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## 🔄 Previously From laravel-role

We were previously at https://github.com/ManiruzzamanAkash/laravel-role, so you need to change the URL if you moved from there

```console
git remote set-url origin git@github.com:laradashboard/laradashboard.git
```

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## ⚙️ How it works

1. Login using Super Admin Credential -
    1. Email - `superadmin@example.com`
    1. Password - `12345678`
1. Forget password - Password forget and reset will work if email is set up properly
1. Create User
1. Create Role
1. Assign Permission to Roles
1. Assign Multiple Role to an User
1. Check by login with the new credentials.
1. If you've not enough permission to do any task, you'll get a warning message.
1. Dashboard with Beautiful chart integrated
1. Module Based Development - Custom Module Add/Enable/Disable/Delete
1. Monitoring - Logging of every action of your application
1. Monitoring - Laravel Pulse
1. Translation Management - Add/Edit/Delete Language, Add/Edit/Delete Translation
1. Settings - General, Site Appearance, Content, Integration settings
1. Admin Menu - Add/Edit/Delete Menu, Submenu, Link
1. Admin Impersonation - Login as another user and switch back to your original account
1. Custom Error Pages - 404, 500, 503, 403
1. Content Management System - Add/Edit/Delete Content, Content Category, Content Tag

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## 📧 Email setup

You can use mailtrap to test emails easily - https://mailtrap.io/ (first need to create mailtrap account and can )

```bash
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=mailtrap-username
MAIL_PASSWORD=mailtrap-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=youremail@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## 📚 Documentation

https://laradashboard.com/docs/

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## Tests

We've used Laravel Pint, Larastan(PHPstan), PHPUnit for formatting, static analysis and testing.

```bash
composer run test
```

**Format:** To format the code, we've used `pint`. You can run the following command to format the code:

```bash
composer run format
```

**Type Safety:** To improve type safety, we've used `rector`. You can run the following command to add type hints:

```bash
composer run type-check
```

You can also run individual commands for each tool (optional):

```bash
composer run pint
composer run phpstan
composer run phpunit
```
<p align="right">(<a href="#readme-top">back to top</a>)</p>

## 📸 Screenshots

### 🔐 Login & Authentication

<table>
  <tr>
    <td width="50%">
      <strong>Login Page</strong><br/>
      <img width="100%" alt="Login Page" src="/demo-screenshots/00-Login-Page-Lite-Mode.png"/>
    </td>
    <td width="50%">
      <strong>Forget Password Page (Dark Mode)</strong><br/>
      <img width="100%" alt="Forget Password Page" src="/demo-screenshots/01-Forget-password.png"/>
    </td>
  </tr>
</table>

### 📊 Dashboard

<table>
  <tr>
    <td width="50%">
      <strong>Dashboard (Light Mode)</strong><br/>
      <img width="100%" alt="Dashboard Light Mode" src="/demo-screenshots/03-Dashboard-Page-lite-Mode.png"/>
    </td>
    <td width="50%">
      <strong>Dashboard (Dark Mode)</strong><br/>
      <img width="100%" alt="Dashboard Dark Mode" src="/demo-screenshots/04-Dashboard-Page-Dark-Mode.png"/>
    </td>
  </tr>
  <tr>
    <td width="50%">
      <strong>Dashboard Collapsed Sidebar</strong><br/>
      <img width="100%" alt="Dashboard Collapsed Sidebar" src="/demo-screenshots/04_1-Dashboard-Collapsed-Sidebar.png"/>
    </td>
  </tr>
</table>

### 🔑 Role Management

<table>
  <tr>
    <td width="50%">
      <strong>Role List (Light Mode)</strong><br/>
      <img width="100%" alt="Role List" src="/demo-screenshots/05-Role-List-Lite.png"/>
    </td>
    <td width="50%">
      <strong>Role List (Dark Mode)</strong><br/>
      <img width="100%" alt="Role List Dark" src="/demo-screenshots/06-Role-List-Dark.png"/>
    </td>
  </tr>
  <tr>
    <td width="50%">
      <strong>Role Create</strong><br/>
      <img width="100%" alt="Role Create" src="/demo-screenshots/07-Role-Create.png"/>
    </td>
    <td width="50%">
      <strong>Role Edit</strong><br/>
      <img width="100%" alt="Role Edit" src="/demo-screenshots/08-Role-Edit.png"/>
    </td>
  </tr>
  <tr>
    <td width="50%">
      <strong>Permission List</strong><br/>
      <img width="100%" alt="Permission List" src="/demo-screenshots/09-Permissions-List-Lite-Mode.png"/>
    </td>
  </tr>
</table>

### 👥 User Management

<table>
  <tr>
    <td width="50%">
      <strong>Users List (Light mode)</strong><br/>
      <img width="100%" alt="Users List (Light mode)" src="/demo-screenshots/10-User-List-Lite-Mode.png"/>
    </td>
    <td width="50%">
      <strong>Users List (Dark mode)</strong><br/>
      <img width="100%" alt="Users List (Dark mode)" src="/demo-screenshots/11-User-List-Dark-Mode.png" />
    </td>
  </tr>
  <tr>
    <td width="50%">
      <strong>User Create</strong><br/>
      <img width="100%" alt="User Create" src="/demo-screenshots/12-User-Create-Lite-Mode.png"/>
    </td>
    <td width="50%">
      <strong>User Delete</strong><br/>
      <img width="100%" alt="User Delete" src="/demo-screenshots/13-User-Delete-Lite-Mode.png" />
    </td>
  </tr>
</table>

### 📝 Content Management - CMS

<table>
  <tr>
    <td width="50%">
      <strong>Posts List</strong><br/>
      <img width="100%" alt="Users List (Light mode)" src="/demo-screenshots/31-Post-List-Lite-Mode.png"/>
    </td>
    <td width="50%">
      <strong>Post Create</strong><br/>
      <img width="100%" alt="Users List (Dark mode)" src="/demo-screenshots/30-Post-List-Dark-Mode.png" />
    </td>
  </tr>
  <tr>
    <td width="50%">
      <strong>Pages List</strong><br/>
      <img width="100%" alt="Users List (Light mode)" src="/demo-screenshots/38-Pages-List-Lite-Mode.png"/>
    </td>
    <td width="50%">
      <strong>Page Delete</strong><br/>
      <img width="100%" alt="Users List (Dark mode)" src="/demo-screenshots/39-Pages-Edit-Dark-Mode.png" />
    </td>
  </tr>
  <tr>
    <td width="50%">
      <strong>Category List & Create</strong><br/>
      <img width="100%" alt="Category List & Create" src="/demo-screenshots/34-Category-List-Lite-Mode.png"/>
    </td>
    <td width="50%">
      <strong>Category Edit</strong><br/>
      <img width="100%" alt="Category Edit" src="/demo-screenshots/35-Category-Edit-Dark-Mode.png" />
    </td>
  </tr>
  <tr>
    <td width="50%">
      <strong>Tag List & Create</strong><br/>
      <img width="100%" alt="Tag Create" src="/demo-screenshots/36-Tags-List-Lite-Mode.png"/>
    </td>
    <td width="50%">
      <strong>Tag Edit</strong><br/>
      <img width="100%" alt="Tag Delete" src="/demo-screenshots/37-Tags-Edit-Dark-Mode.png" />
    </td>
  </tr>
</table>

### 🧩 Module Management

<table>
  <tr>
    <td width="50%">
      <strong>Module List</strong><br/>
      <img width="100%" alt="Module List" src="/demo-screenshots/14-Module-List.png"/>
    </td>
    <td width="50%">
      <strong>Upload Module</strong><br/>
      <img width="100%" alt="Upload Module" src="/demo-screenshots/15-Module-Upload.png"/>
    </td>
  </tr>
</table>

### ⚙️ Settings Pages

<table>
  <tr>
    <td width="50%">
      <strong>General Settings</strong><br/>
      <img width="100%" alt="General Settings" src="/demo-screenshots/40-Settings-General.png"/>
    </td>
    <td width="50%">
      <strong>Site Appearance</strong><br/>
      <img width="100%" alt="Site Appearance" src="/demo-screenshots/41-Settings-Site-Appearance-Dark-Mode.png"/>
    </td>
  </tr>
  <tr>
    <td width="50%">
      <strong>Content Settings</strong><br/>
      <img width="100%" alt="Content Settings" src="/demo-screenshots/42-Settings-Content.png"/>
    </td>
    <td width="50%">
      <strong>Integration Settings</strong><br/>
      <img width="100%" alt="Integration Settings" src="/demo-screenshots/43-Settings-Integration.png"/>
    </td>
  </tr>
</table>

### 🌐 Translations Pages

<table>
  <tr>
    <td width="50%">
      <strong>Translations List</strong><br/>
      <img width="100%" alt="Translations List" src="/demo-screenshots/50-Translation-List-Lite-Mode.png" />
    </td>
    <td width="50%">
      <strong>Add Language</strong><br/>
      <img width="100%" alt="Create Translation" src="/demo-screenshots/51-Translation-Create-Dark-Mode.png" />
    </td>
  </tr>
  <tr>
    <td width="50%">
      <strong>Language Switcher</strong><br/>
      <img width="100%" alt="Language Switcher" src="/demo-screenshots/90-Language-Switcher.png" />
    </td>
    <td width="50%">
      <!-- Reserved for future screenshot -->
    </td>
  </tr>
</table>

### 📊 Monitoring

<table>
  <tr>
    <td width="50%">
      <strong>Action Logs</strong><br/>
      <img width="100%" alt="Action Logs" src="/demo-screenshots/20-Action-Log-List.png"/>
    </td>
    <td width="50%">
      <strong>Laravel Pulse</strong><br/>
      <img width="100%" alt="Laravel Pulse" src="/demo-screenshots/91-Laravel-Pulse-Dashboard-for-Monitoring.png"/>
    </td>
  </tr>
</table>

### 🔧 Other Pages / Sections / Tests

<table>
  <tr>
    <td width="50%">
      <strong>Custom Error Pages</strong><br/>
      <img width="100%" alt="Custom Error Pages" src="/demo-screenshots/100-Custom-Error-Pages.png"/>
    </td>
    <td width="50%">
      <strong>Post activity Chart</strong><br/>
      <img width="100%" alt="Post activity Chart" src="/demo-screenshots/102-Post-activity-Chart.png"/>
    </td>
  </tr>
  <tr>
    <td width="50%">
      <strong>PHPUnit, Pint, Rector, PHPstan tests</strong><br/>
      <img width="100%" alt="PHPUnit, Pint, Rector, PHPstan tests" src="/demo-screenshots/103-Unit-Tests-Demo.png"/>
    </td>
  </tr>
</table>

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## 🔗 Live Demo

https://demo.laradashboard.com

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## ✨ Premium Features

Please visit at Lara Dashboard to get more premium moduels - https://laradashboard.com. Premium modules included CRM, HRM, Course Managements and so on.

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## 🧩 Core modules

-   **User Avatar** - https://github.com/laradashboard/UserAvatar - A very simple module create an avatar for a user. Handle migration, entries/updates in user forms and so on.

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## 👥 Contributing

Want to contribute? Fork the project, make your changes, and submit a pull request. Even small improvements to documentation are appreciated!

Please be sure to read our [Contribution Guide](CONTRIBUTING.md) before submitting your PR.

### 🌟 Top contributors:

<a href="https://github.com/laradashboard/laradashboard/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=laradashboard/laradashboard" alt="contrib.rocks image" />
</a>

## 💖 Support

If you like my work you may consider buying me a ☕ / 🍕

<a href="https://www.patreon.com/maniruzzaman" target="_blank" title="Buy Me A Coffee">
    Go to Patreon
</a>

<p align="right">(<a href="#readme-top">back to top</a>)</p>

## 📞 Connect

-   Join Facebook Community (For any questions, latest updates) - https://www.facebook.com/groups/laradashboard
-   Linkedin Community - https://www.linkedin.com/groups/14690156
-   Youtube channel (For tutorials) - https://www.youtube.com/@laradashboard
-   Maniruzzaman Akash - [@LinkedIn](https://www.linkedin.com/in/maniruzzamanakash) | manirujjamanakash@gmail.com

<p align="right">(<a href="#readme-top">back to top</a>)</p>

<!-- https://www.markdownguide.org/basic-syntax/#reference-style-links -->

[contributors-shield]: https://img.shields.io/github/contributors/laradashboard/laradashboard.svg?style=for-the-badge
[contributors-url]: https://github.com/laradashboard/laradashboard/graphs/contributors
[forks-shield]: https://img.shields.io/github/forks/laradashboard/laradashboard.svg?style=for-the-badge
[forks-url]: https://github.com/laradashboard/laradashboard/network/members
[stars-shield]: https://img.shields.io/github/stars/laradashboard/laradashboard.svg?style=for-the-badge
[stars-url]: https://github.com/laradashboard/laradashboard/stargazers
[issues-shield]: https://img.shields.io/github/issues/laradashboard/laradashboard.svg?style=for-the-badge
[issues-url]: https://github.com/laradashboard/laradashboard/issues
[license-shield]: https://img.shields.io/github/license/laradashboard/laradashboard.svg?style=for-the-badge
[license-url]: https://github.com/laradashboard/laradashboard/blob/master/LICENSE.txt
[linkedin-shield]: https://img.shields.io/badge/-LinkedIn-black.svg?style=for-the-badge&logo=linkedin&colorB=555
[linkedin-url]: https://linkedin.com/in/maniruzzamanakash
[product-screenshot]: images/screenshot.png
[Next.js]: https://img.shields.io/badge/next.js-000000?style=for-the-badge&logo=nextdotjs&logoColor=white
[Next-url]: https://nextjs.org/
[React.js]: https://img.shields.io/badge/React-20232A?style=for-the-badge&logo=react&logoColor=61DAFB
[React-url]: https://reactjs.org/
[Vue.js]: https://img.shields.io/badge/Vue.js-35495E?style=for-the-badge&logo=vuedotjs&logoColor=4FC08D
[Vue-url]: https://vuejs.org/
[Angular.io]: https://img.shields.io/badge/Angular-DD0031?style=for-the-badge&logo=angular&logoColor=white
[Angular-url]: https://angular.io/
[Svelte.dev]: https://img.shields.io/badge/Svelte-4A4A55?style=for-the-badge&logo=svelte&logoColor=FF3E00
[Svelte-url]: https://svelte.dev/
[Laravel.com]: https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white
[Laravel-url]: https://laravel.com
[Bootstrap.com]: https://img.shields.io/badge/Bootstrap-563D7C?style=for-the-badge&logo=bootstrap&logoColor=white
[Bootstrap-url]: https://getbootstrap.com
[JQuery.com]: https://img.shields.io/badge/jQuery-0769AD?style=for-the-badge&logo=jquery&logoColor=white
[JQuery-url]: https://jquery.com
[PHP.com]: https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white
[PHP-url]: https://www.php.net
[JavaScript.com]: https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black
[JavaScript-url]: https://developer.mozilla.org/en-US/docs/Web/JavaScript
[MySQL.com]: https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white
[MySQL-url]: https://www.mysql.com
[TailwindCSS.com]: https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white
[TailwindCSS-url]: https://tailwindcss.com
[AlpineJS.com]: https://img.shields.io/badge/Alpine.js-8BC0D0?style=for-the-badge&logo=alpine.js&logoColor=black
[AlpineJS-url]: https://alpinejs.dev


## VICIdial browser calling

The optional VICIdial mode uses the existing SIP.js browser phone for audio and a
server-side Laravel proxy for the VICIdial Agent API. The separate test page
`/admin/vicidial` is available to explicitly mapped users when enabled.
`/admin/dialer` retains the existing dialer interface and FreeSWITCH behavior.
No schema migration is required. API credentials never go to the browser; the
browser necessarily receives its own SIP phone credentials.

### Configure before deployment

For unified Docker, add these settings to the repository-root `.env` (or the
Compose env file you already use). For a native Laravel deployment, use
`laravel/.env`. Do not commit credentials. Use the exact WSS/SIP settings from
your existing working VICIdial webphone, not the example hostnames below.

```dotenv
VICIDIAL_ENABLED=true
VICIDIAL_API_URL=https://vici.example.com/agc/api.php
VICIDIAL_API_USER=your_api_user
VICIDIAL_API_PASSWORD='your_api_password'
VICIDIAL_AGENT_URL=https://vici.example.com/agc/vicidial.php
VICIDIAL_WSS_URL=wss://vici.example.com:8089/ws
VICIDIAL_SIP_DOMAIN=vici.example.com
VICIDIAL_ICE_SERVERS='[]'
VICIDIAL_AGENTS='{"7":{"agent_user":"agent7","sip_username":"phone7","sip_password":"your_phone_password"}}'
VICIDIAL_DISPOSITIONS=SALE,NI
```

Replace `7` with the local application user ID, and replace the agent and phone
identities with the actual VICIdial assignments. Each user must have a distinct
agent/phone mapping. Do not reuse the API account as a phone account. Configure
only disposition codes your campaign allows; scheduled callbacks require the
VICIdial agent screen. The API user needs Agent API Access and permission to
control the mapped agents/functions. URLs must use HTTPS/WSS with trusted TLS
certificates; redirects and embedded credentials in URLs are not accepted.

Optional customer lookup: add `crm_agent_id` and `crm_team_id` to the user's
mapping. This uses `st_get_agent_active_lead`, whose CRM agent ID must match the
VICIdial user's `custom_three` field and whose team ID must follow your installed
integration's requirements. Without this mapping, use the VICIdial screen for
customer details. Lookup failures stop polling and are displayed; the refresh
button retries it. Agent readiness/customer state is authoritative in VICIdial,
not inferred from the persistent phone connection.

### Manual deployment (the assistant does not run these commands)

The unified image copies application code, so a plain container restart does not
install source changes. From the repository root, using your usual Compose env
file/options if different:

```bash
docker compose -f docker-compose.unified.yml build app
docker compose -f docker-compose.unified.yml up -d --no-deps app
```

The entrypoint refreshes Laravel's production caches. No FreeSWITCH rebuild or
restart is needed. For a native/source-mounted Laravel deployment, deploy the
files and refresh cached config/routes/views with `php artisan optimize:clear`
and your normal production cache command. This integration reuses the existing
`window.DialerWebRTC` bundle; the new page controller is a plain public JavaScript
file and adds no npm dependency. If existing frontend assets do not already
contain that browser-phone class, build the frontend manually using the project's
normal process.

### Receive a call

1. Open `/admin/vicidial` as the mapped application user and connect the browser phone.
2. Open the linked VICIdial agent login, sign in with the assigned agent, phone,
   and campaign, and keep the screen open. Disable its other embedded webphone
   for the same extension so registrations do not compete. This integration
   uses a normal persistent agent session, not on-hook/remote-agent mode.
3. If agent audio does not ring automatically, select Request agent audio.
   This sends `call_agent` with `value=CALL`; it does not create an agent login.
4. Accept agent audio, enable browser playback if needed, then select Ready.
   Customer calls flow through the persistent conference connection, so there
   is not necessarily a new SIP invitation or accept button for each customer.
5. End customer call uses `external_hangup`, preserving agent audio. Once the
   customer call ends, submit a disposition; pause/resume as required.
6. Pause/log out in VICIdial before closing the browser. Page exit attempts a
   best-effort API pause, but network/browser shutdown can prevent delivery.

Call history, recordings, and authoritative customer call state remain in
VICIdial. These calls are not inserted into FreeSWITCH call logs or controlled
with FreeSWITCH UUID endpoints. Keeping the existing agent screen open is
required; this is not a headless replacement for VICIdial's agent application.
Direct WSS/WebRTC avoids opening local inbound SIP ports but does not hide your
public IP from VICIdial. IP privacy requires separately configured signaling
and media relays.

Reference: https://www.vicidial.org/docs/AGENT_API.txt

### Internal VICIdial phone test and private CA

Browser registration works with the assigned SIP phone credentials before API
credentials are configured. The page displays phone test mode and disables API
controls until API credentials exist. A registered status is shown only after
SIP.js reports registration acceptance. This does not establish an agent session
or verify a customer call or media path.

For a private HTTPS CA, put its public PEM certificate in
`docker/vicidial-certs/lab-ca.crt` and set
`VICIDIAL_CA_BUNDLE=/etc/vicidial-certs/lab-ca.crt` in the Compose environment.
The app mounts that directory read-only; the API client uses the configured CA
for certificate verification. Firefox must trust the CA separately for WSS.
Never copy the CA private key. The certificate directory is ignored by git.

After deployment, open `/admin/vicidial` as the mapped user and choose Connect browser phone.
Confirm the peer has a registration contact in Asterisk before originating a
lab test call. API Ready/Pause controls additionally need a VICIdial campaign,
a logged-in agent session and a permitted API account.
