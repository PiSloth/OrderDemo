# Digital Asset Security Management System (Phase 1)
## User & Administrator Manual

---

## 1. Introduction & Security Policy Overview

The **Digital Asset Security Management System** is a software account inventory and compliance management module built into the company application. It provides visibility and control over all company-owned software platforms, account identities, Persons in Charge (PICs), security configurations (such as Multi-Factor Authentication and recovery mechanisms), and physical/authorized device links.

> [!IMPORTANT]
> **Strict Phase 1 Security Policy: Metadata Only**
> This system stores **only security metadata and compliance records**.
> - **DO NOT** enter plain-text passwords, recovery codes, multi-factor backup codes, private API keys, or OAuth secrets into any form, note field, or property value.
> - Evidence files containing screenshots of credentials must have passwords and secret keys obscured or redacted before upload.
> - Credential and password vault management is handled under a separate, independently audited module (Phase 2).

---

## 2. Navigation & User Interface

The module is integrated directly into the main application navigation sidebar under the **"Digital Asset"** expandable menu (marked with a shield icon).

### Menu Sections:
1. **Accounts** (`/digital-assets/accounts`): The primary workspace. View, search, filter, and manage all software accounts and drill into individual account details.
2. **Platforms** (`/digital-assets/platforms`): Manage the company's catalog of third-party software vendors, cloud providers, social networks, and email platforms.
3. **Properties Config** (`/digital-assets/property-definitions`): Define security requirements, validation rules, approval criteria, and review intervals globally or per platform.
4. **Reviews** (`/digital-assets/reviews`): Schedule, execute, and track compliance reviews.
5. **Devices** (`/digital-assets/devices`): Registry of company workstations, laptops, mobile devices, and employee BYOD devices authorized to access accounts.
6. **Audit Logs** (`/digital-assets/audit-logs`): Full immutable event trail of all administrative and security actions.

---

## 3. Platform Catalog Management (`/digital-assets/platforms`)

Before creating accounts, the target platform must exist in the platform catalog.

### 3.1 Viewing Platforms
- Navigate to **Digital Asset > Platforms**.
- You will see a table displaying Platform Name, Code, Category, Status, and Active Account Count.

### 3.2 Adding a New Platform
1. Click the **+ New Platform** button in the top right.
2. Complete the form:
   - **Platform Code**: A unique alphanumeric slug (e.g., `google_workspace`, `github_corp`, `aws_cloud`, `slack`). Once created, codes should remain consistent.
   - **Display Name**: Human-readable name (e.g., *Google Workspace*, *GitHub Enterprise*).
   - **Category**: Select the appropriate category:
     - `email` (e.g., Gmail, Microsoft Exchange)
     - `cloud` (e.g., AWS, GCP, Azure, DigitalOcean)
     - `source_control` (e.g., GitHub, GitLab, Bitbucket)
     - `social_media` (e.g., Facebook, TikTok, LinkedIn, YouTube)
     - `communication` (e.g., Slack, Discord, Zoom)
     - `business_app` (e.g., Jira, Notion, Salesforce)
     - `other`
   - **Status**: Set to `active` or `inactive`. Only active platforms can be chosen when creating new accounts.
3. Click **Create Platform**.

### 3.3 Deleting or Retiring a Platform
- To prevent orphaned accounts, the system will **reject deletion** of any platform that currently has accounts linked to it.
- To retire a platform, edit its status from `active` to `inactive`.

---

## 4. Software Account Inventory (`/digital-assets/accounts`)

The **Accounts** page is the central hub for monitoring company digital assets.

### 4.1 Filter and Search
- **Search**: Search across account display names and usernames.
- **Platform Filter**: Filter accounts by specific platforms (e.g., only show Google accounts).
- **Lifecycle Status**:
  - `active`: Accounts currently in operational use.
  - `suspended`: Temporarily frozen or deactivated accounts.
  - `retired`: Decommissioned accounts preserved for historical audit.
- **Security Status**:
  - `compliant`: Account has passed review and fulfills all required security controls.
  - `needs_review`: Account review interval expired, or pending changes exist.
  - `at_risk`: Required security properties (such as 2FA) are missing or disabled.
  - `unknown`: Newly added account not yet verified.

### 4.2 Registering a New Account
1. From the Accounts page, click **+ New Account**.
2. Complete the initial registration:
   - **Platform**: Select an active platform from the dropdown.
   - **Account Name / Label**: Descriptive name (e.g., *Company Main Marketing Account*, *Billing Admin AWS*).
   - **Username / Identifier**: The username, handle, or login identifier used on that platform (e.g., `admin@company.com`, `billing-lead`).
   - **Initial Lifecycle Status**: Usually `active`.
3. Click **Create Account**. You will be redirected directly to the **Account Detail Workspace**.

---

## 5. Account Detail Workspace (`/digital-assets/accounts/{id}`)

The account detail view gives a 360-degree view of an account's security configuration, personnel, evidence, and devices across several interactive tabs.

### 5.1 Security Properties & Configuration (Properties Tab)
This tab displays all configured security properties defined for this account and platform.

#### Updating a Security Property
1. In the **Security Properties** card, click the **Update Properties** or **Edit** button.
2. Select the property to update (e.g., `two_factor_enabled`, `last_password_changed_at`, `login_methods`).
3. Enter the new value:
   - **Boolean**: Checked (Yes) or Unchecked (No).
   - **Date/DateTime**: Provide the ISO date/timestamp when the action occurred.
   - **Email Reference**: Select a valid email from the company's verified email directory.
   - **Choice / Multi-choice**: Select allowed values (e.g., `app_authenticator`, `security_key`).
4. Enter a **Change Reason** (mandatory for audit compliance).
5. Click **Save Property Change**.

> [!NOTE]
> **Approval Workflow for Sensitive Properties**
> If a property definition has `requires_approval = true` enabled (such as disabling 2FA or changing primary recovery emails):
> - The new value will **not** immediately overwrite the live property.
> - It is recorded as a `pending` change in the property history.
> - An authorized manager must review and click **Approve** or **Reject** on the history entry before the change takes effect.

---

### 5.2 Persons in Charge (PICs Tab)
Every software account must have identifiable individuals responsible for its upkeep and credentials.

#### Assigning a PIC
1. In the **Persons in Charge** tab, click **+ Assign PIC**.
2. Select an existing company employee from the **User** dropdown.
3. Select their assignment **Role**:
   - `primary`: Main custodian responsible for operations and security.
   - `secondary`: Backup custodian.
   - `approver`: Management or security lead with approval authority.
   - `viewer`: Read-only stakeholder.
4. Specify **Permissions / Scope** (e.g., `Full Admin Access`, `Billing only`).
5. Click **Assign PIC**.

#### Revoking a PIC
1. Locate the active PIC in the list.
2. Click **Revoke**.
3. Enter the reason for revocation (e.g., *Employee transferred department*, *Contract completed*).
4. Click **Confirm Revocation**.
- The user is immediately removed from active status.
- The system automatically writes an immutable log to `software_account_pic_history`.

---

### 5.3 Linked Emails Tab
Company accounts are frequently registered with, recovered by, or send notifications to specific corporate mailboxes.

#### Linking an Email
1. Open the **Linked Emails** tab and click **+ Link Email**.
2. Select an email address from the existing master email database (`email_lists`).
3. Select the **Purpose**:
   - `registration`: The primary email used during original account sign-up.
   - `recovery`: The secondary or backup email configured for password resets and verification challenges.
   - `contact`: General billing or inquiry contact.
   - `notification`: Automated alert receiver.
   - `other`
4. Toggle **Primary Email** if this is the account's designated primary address.
5. Click **Link Email**.

---

### 5.4 Authorized Devices Tab
Tracks authorized company or personal hardware associated with this account.

#### Linking a Device to an Account
1. Open the **Authorized Devices** tab and click **+ Link Device**.
2. Select a registered device (or register one first via the Devices catalog).
3. Select **Device Status** (`active`, `revoked`).
4. Click **Link Device**.
5. When a device is verified as having active MFA tokens or valid session certificates, click **Verify** to stamp the verification timestamp.

---

### 5.5 Evidence & Verification Tab
Store proof of compliance (e.g., screenshots showing 2FA enabled, dashboard export PDFs, authorization sign-off documents).

#### Uploading Evidence
1. Navigate to the **Evidence & Files** tab and click **+ Upload Evidence**.
2. Choose a file:
   - Allowed formats: `.pdf`, `.png`, `.jpg`, `.jpeg`.
   - Maximum size: 10 MB per file.
   - Ensure credentials and secrets are obscured prior to uploading.
3. Select **Evidence Type**:
   - `screenshot`
   - `approval_form`
   - `audit_export`
   - `configuration_file`
4. Enter a descriptive title and any notes.
5. Click **Upload**.
   - The file is saved directly to private local storage (inaccessible via public web URLs).
   - An immutable SHA-256 cryptographic checksum is generated and saved.

#### Verifying or Rejecting Evidence
1. Security leads or reviewers click **Review / Verify** on the evidence item.
2. Actions:
   - **Accept / Verify**: Marks status as `accepted` with a valid-until date.
   - **Reject**: Marks status as `rejected` with mandatory rejection notes explaining why the proof is insufficient.

#### Downloading Private Evidence
- Click **Download File** on any evidence item.
- The request passes through server-side authorization checks and is streamed directly to your browser session.

---

## 6. Dynamic Property Definitions (`/digital-assets/property-definitions`)

Property definitions allow administrators to adapt security requirements across different platforms without code changes or database migrations.

### 6.1 Creating a Property Definition
1. Navigate to **Digital Asset > Properties Config**.
2. Click **+ New Property Definition**.
3. Configure the fields:
   - **Scope**: Choose whether this property applies **Globally** (all platforms) or to a **Specific Platform** (e.g., only GitHub).
   - **Property Code**: Unique key (e.g., `sso_enforced`, `ip_allowlist_enabled`).
   - **Display Label**: Label shown in user forms (e.g., *Single Sign-On Enforced*).
   - **Data Type**:
     - `boolean`: True/False checkbox.
     - `text`: Single or multi-line text string.
     - `integer` / `decimal`: Numerical input.
     - `date` / `datetime`: Calendar date or timestamp.
     - `email_reference`: Dropdown linking to corporate email records.
     - `single_choice` / `multi_choice`: Predefined allowed options (defined in validation rules).
   - **Is Required**: If checked, account reviews will fail unless this property is set.
   - **Evidence Required**: If checked, reviews will require verified evidence files linked to this property.
   - **Requires Approval**: If checked, value updates require explicit managerial approval before taking effect.
   - **Review Interval (Days)**: How often this property must be re-verified (e.g., `90` days).
4. Click **Save Definition**.

---

## 7. Security Reviews & Compliance Workflow (`/digital-assets/reviews`)

Scheduled and ad-hoc reviews ensure company accounts remain compliant with security policies over time.

### 7.1 Scheduling a Review
1. Navigate to **Digital Asset > Reviews**.
2. Click **+ Schedule Review**.
3. Select the **Account** to be reviewed.
4. Select **Review Type**:
   - `scheduled`: Standard quarterly or annual compliance audit.
   - `ad_hoc`: Incident investigation, personnel offboarding, or ad-hoc check.
5. Set the **Scheduled Date**.
6. Click **Schedule**.

### 7.2 Starting a Review
1. Click **Start Review** on any scheduled review item.
2. The review status shifts to `in_progress`.

### 7.3 Completing a Review
1. When all items on the review checklist are satisfied, click **Complete Review**.
2. The server-side verification engine performs an automated policy check:
   - Ensures all **mandatory property definitions** are present and valid on the account.
   - Ensures that any properties requiring evidence have **accepted, valid evidence files** attached.
3. Select the final **Review Outcome**:
   - `compliant`: Account satisfies all requirements. Account security status updates to `compliant`.
   - `needs_attention`: Minor non-blocking findings. Account security status updates to `needs_review`.
   - `at_risk`: Critical failures identified. Account security status updates to `at_risk`.
4. Enter summary findings and notes.
5. Click **Submit Completion**.
   - The review is marked completed.
   - The account's `last_reviewed_at` is updated to the current timestamp.
   - The account's `next_review_at` is calculated and updated based on the platform's review interval.

---

## 8. Physical & Authorized Devices (`/digital-assets/devices`)

Maintains an inventory of hardware that accesses software accounts.

### 8.1 Registering a Device
1. Navigate to **Digital Asset > Devices** and click **+ Register Device**.
2. Complete device details:
   - **Device Identifier / Serial Number**: Unique corporate asset tag or serial number.
   - **Device Name**: E.g., *MacBook Pro M3 - Design Lead*.
   - **Device Type**: `laptop`, `desktop`, `mobile`, `tablet`, or `server`.
   - **Ownership**: `company_owned` or `byod` (Bring Your Own Device).
   - **Fixed Asset Link**: Optionally link to an existing office asset record from the company's asset inventory (`office_assets`).
   - **Operating System & Version**: E.g., *macOS Sonoma 14.5*, *Windows 11 Enterprise*.
3. Click **Register Device**.

---

## 9. Security Audit Logs (`/digital-assets/audit-logs`)

The system provides an immutable audit trail for security accountability.

### 9.1 Viewing Logs
- Navigate to **Digital Asset > Audit Logs**.
- View timestamped events including:
  - Account creation and lifecycle changes.
  - Security property modifications, approvals, and rejections.
  - PIC assignments and revocations.
  - Evidence uploads, verifications, and download events.
  - Device links and verifications.
- **Filtering**: Filter by Event Type, Software Account, or Action Performer.
- **JSON Payload Viewer**: Click **View Details** to view full before/after snapshots and metadata.

> [!NOTE]
> **Automatic Redaction Engine**
> The audit service automatically strips any sensitive dictionary keys (such as `password`, `secret`, `token`, `key`, `otp`, `recovery_code`) from logged JSON payloads to guarantee privacy.

---

## 10. Frequently Asked Questions & Troubleshooting

### Q1: Why was my security property change marked as "Pending"?
**A**: That specific property definition has `requires_approval` enabled. A designated manager or reviewer must view the property history on the account page and click **Approve** before the value becomes active.

### Q2: Why did my review completion fail with "Mandatory properties missing"?
**A**: Your account is missing one or more required security properties (such as `two_factor_enabled` or `recovery_configured`), or a required property is missing an accepted evidence attachment. Navigate to the account's **Properties** and **Evidence** tabs, provide the missing configuration and proof, and try completing the review again.

### Q3: How do I store a password or recovery code for an account?
**A**: **You do not.** Phase 1 is strictly restricted to metadata (e.g., "Is 2FA enabled? Yes", "When was the password changed? 2026-10-12"). Actual credentials must not be entered anywhere in this system.

### Q4: Can two employees be designated as the active Primary PIC?
**A**: Yes, multiple PICs can be assigned, but an individual employee can only hold one active assignment per account at a time. The database enforces this via unique constraints.
