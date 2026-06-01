# 🏥 DBU Campus Blood Bank Management System (BBMS)
**Debre Berhan University | College of Computing and Informatics | Department of Information Systems**

An enterprise-grade, web-based platform designed to digitize, monitor, optimize, and secure the end-to-end lifecycle of voluntary blood donations and multi-institutional blood product distribution within the Debre Berhan University campus clinic and its surrounding healthcare network.

---

## 👥 Project Development Group

| # | Student Name | Student ID | Department | Role |
|---|--------------|------------|------------|------|
| 1 | **Mihret Alemayehu** | `1601379` | 
| 2 | **Abebech Nega** | `1601466` | 
| 3 | **Gebeyanesh Smegnew** | `1601192` | 
| 4 | **Fantayenesh Worku** | `1601171` | 
| 5 | **Kalkidan Lemma** | `1601275` | 

* **Instructor:** Mr. Getachew
* **Academic Year:** 2026

---

## 🚀 Key Functional Modules

The system is logically divided into three isolated execution paths to prevent data corruption and maintain clinical accuracy:

### 1. Pre-Donation User Journey (Public & Donor)
* **Registration & Planned Dates:** Donors register with their DBU ID, contact info, and select a **Planned Donation Date** for when they intend to visit the clinic. 
* **Unknown Blood Type Handling:** If a donor does not know their blood type, they register as `Unknown`. Their account is placed in a `pending_verification` state until a laboratory screening is completed.
* **Smart Eligibility Checker:** Evaluates whether a donor is fit to donate based on clinical resting windows (90 days for men, 120 days for women) from their last physical donation.
* **Personalized Dashboard:** Upload profile photos, update contact details, view eligibility timers, and download printable appreciation certificates.

### 2. Post-Donation Laboratory Pipeline (Laboratory Staff)
* **Pathogen Screening & Verification:** Lab technicians screen blood samples for infectious markers: **HIV, Hepatitis B/C, and Syphilis**.
* **Blood Type Verification:** If a donor's blood type was `Unknown`, the technician verifies and selects their actual blood type upon entering negative screening results.
* **Auto-Inventory Stocking:** Saving a **Safe** test result automatically increments the respective blood type's inventory stock level by **+1 unit**.
* **Automated SMTP Health Interlocks:**
  - **Safe Results:** Sends an email confirming the donor is healthy, verifying their blood type, and unlocking their certificate.
  - **Unsafe Results:** Locks the donor's account (`status = 'blocked'`), flags eligibility as `unsafe`, and dispatches an email alerting them of the positive marker(s) found (HIV, Hepatitis, Syphilis) while instructing them to report to the campus clinic for private counseling.

### 3. Institutional Portal & Emergency Services
* **Hospital Portal:** Secure portal for authorized health facilities (e.g., Debre Berhan Referral Hospital) to log in, view real-time DBU blood inventory levels, and submit requests for specific units of blood.
* **Public Emergency Request:** A public-facing form allowing individuals to submit emergency blood requests.
* **Immediate Stock Deduction & Reservation:**
  - When a request is submitted (publicly or via the Hospital Portal), the system checks if the required units are available in stock.
  - If available, the requested units are **immediately reserved and deducted** from inventory, ensuring no double-allocation.
  - If a pending request is deleted or rejected by the admin, the reserved units are **automatically returned** to the active blood stock.
* **Fulfillment Token:** Upon admin approval, the system dispatches an email containing a secure alphanumeric verification token to the requester/hospital for courier pickup validation.

---

## 🛠️ Technology Stack & Requirements

* **Language & Logic:** PHP 7.4+ (fully object-oriented connection managers with prepared SQL statements)
* **Database System:** MySQL 5.7+ / MariaDB (runs offline natively on XAMPP).
  - *Fallback Feature:* Auto-detects environment and drops back to an SQLite3 local database if MySQL is unavailable (ideal for Replit/demo hosting).
* **Mailing Client:** PHPMailer SMTP client integrated with secure TLS (port 587) or SSL (port 465).
* **Frontend Design:** Vanilla CSS with local Bootstrap 5.0 (No internet connection required; all stylesheets and JS bundles are stored locally in `/assets/`).

---

## 🔑 Default Login Credentials

For testing and demonstration, use the following default profiles:

| Portal Role | Access URL | Username / Email | Password |
|-------------|------------|------------------|----------|
| **System Administrator** | `/admin_login.php` | `admin@dbu.edu.et` | `admin123` |
| **Voluntary Donor** | `/login.php` | *(Register a new donor account or login)* | *(User-created)* |
| **Partner Hospital** | `/hospital_login.php` | *(Register via portal, admin activates)* | *(User-created)* |

---

## 📁 Directory Structure

```
dbu_blood_bank/
├── assets/
│   ├── css/bootstrap.min.css          ← Local Bootstrap 5 CSS
│   ├── js/bootstrap.bundle.min.js     ← Local Bootstrap 5 JS
│   └── images/
│       ├── logo/                      ← Admin-uploaded university logo
│       └── team/                      ← Team photos
├── includes/
│   ├── head.php                       ← Page layouts, SEO tags, CSS styles
│   ├── nav.php                        ← Responsive navigation bar
│   └── footer.php                     ← Page footers (Feminist Group)
├── uploads/
│   └── gallery/                       ← Donation photos
├── config.php                         ← Singleton Database Manager & SQL Helpers
├── index.php                          ← System landing page
├── login.php                          ← Donor portal login
├── register.php                       ← Donor registration (Planned date input)
├── dashboard.php                      ← Donor dashboard & profile manager
├── admin_login.php                    ← Administrator login
├── admin_panel.php                    ← Admin control panel (7 functional tabs)
├── hospital_login.php                 ← Hospital login page
├── hospital_dashboard.php             ← Hospital control panel & request form
├── certificate.php                    ← Printable donor certificate of appreciation
├── test_results.php                   ← Lab technician screening portal
├── notifications_log.php              ← Live SMTP simulated notification logs
├── pdf_report.php                     ← Printable full-system database reports
├── about.php                          ← Mission, vision, and 2026 timeline
├── eligibility.php                    ← Smart eligibility calculator
├── request.php                        ← Public blood request submission
├── search.php                         ← Public registry donor search
├── blood_stock.php                    ← Live blood stock indicators
├── gallery.php                        ← Donation photo gallery
├── tips.php                           ← Donation guidelines & tips
└── logout.php                         ← Logout handler
```

---
