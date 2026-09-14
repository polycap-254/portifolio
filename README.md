# Polycap Portfolio

Personal portfolio website for **Polycap Nyamongo Maturwe** — Mathematics & Computer Studies Educator, Web Developer, and Technology Enthusiast.

Built with **HTML5, CSS3, Vanilla JavaScript, PHP 8+, MySQL/MariaDB**, and **Apache (XAMPP)**. No frameworks.

---

## Quick Start (XAMPP on Windows)

1. Install XAMPP → start **Apache** and **MySQL**.
2. Clone or copy this folder into `C:\xampp\htdocs\polycap-portfolio\`.
3. Import the database:
   - Open http://localhost/phpmyadmin
   - **Import** → `database/polycap_portfolio.sql`
4. Visit:
   - Site:  http://localhost/polycap-portfolio/
   - Admin: http://localhost/polycap-portfolio/admin/

**Default admin credentials** (change immediately):
- Username: `admin`
- Password: `Admin@123`

> Ports: XAMPP may run Apache on port 80 or 81 depending on your setup. Adjust URLs accordingly.

---

## Project Structure
polycap-portfolio/
├── index.php Public home page (all sections)
├── contact.php Contact form POST handler
├── admin/ Admin panel (login, dashboard, CRUD)
├── config/ Database connection
├── includes/ Shared header, navbar, footer, auth, helpers
├── assets/ CSS, JS, images
├── uploads/ User-uploaded images + CV
└── database/ SQL schema + seed data
---

## Features

- Responsive layout (desktop / tablet / mobile)
- Dark + light mode (persisted in localStorage)
- Hero, About (modal cards), Education timeline, Skills tabs, Projects grid, Experience timeline, Services, Contact
- Project filtering + project detail modal
- Contact form → stored in MySQL (with CSRF + server-side validation)
- Admin panel: profile, education, skills, projects, experience, services, social links, messages
- Password hashing, prepared statements, session auth, CSRF tokens, upload whitelisting

---

## Documentation

- **Installation**: see Quick Start above (full guide coming in Phase 8)
- **Customization checklist**: coming in Phase 8
- **Deployment**: coming in Phase 8

---
## Related Projects

- **ShopEasy** — Demo e-commerce site: https://shoponline.10001mb.com

© 2026 Polycap Nyamongo Maturwe. All Rights Reserved.
