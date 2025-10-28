# bms
---

# 📘 Book Management System (BMS)

## 🚀 Project Scope

### 🧩 Overview

The **Book Management System (BMS)** is a PHP-MySQL web application designed to manage books and users efficiently with **role-based access control**.
It enables administrators to perform CRUD operations on both **books (as products)** and **users**, while maintaining secure authentication and granular permission control.

The system provides a responsive Bootstrap interface, real-time search using DataTables, and a modular architecture for easy extension.

---

## 🎯 Objectives

* Simplify the management of books and user accounts.
* Enforce **role-based permissions** for secure access.
* Enable **CRUD operations** (Create, Read, Update, Delete) for both books and users.
* Allow image uploads for book covers with validation.
* Provide a dashboard with essential statistics and summaries.

---

## ⚙️ Functional Scope

### 🧑‍💼 User Management

* **User Authentication:** Secure login, registration, and logout with `password_hash()` and session handling.
* **Roles & Permissions:**

  * `Super Admin` – full control over users, roles, and books.
  * `Admin` – can manage books and standard users.
  * `User` – can view books only.
* **User CRUD:** Add, edit, delete, or deactivate users.
* **Role Assignment:** Manage which roles can perform specific operations.

---

### 📚 Book Management (Product Module)

* **Book CRUD:** Add, update, delete, and view books with details like title, author, publisher, year, and category.
* **Cover Image Upload:**

  * Upload and store book cover images in `assets/uploads/`.
  * Validate MIME type and file size.
  * Display thumbnails in listings and full-size in detailed views.
* **Categorization:** Group books by categories/genres.
* **Search & Filters:** Real-time search and sorting using DataTables or AJAX.
* **Soft Delete:** Move deleted books to a “Trash” view for recovery.

---

### 📊 Dashboard & Reports

* **Admin Dashboard:** Summary of total books, users, and categories.
* **Reports:** Export and import books in CSV/Excel format.
* **Charts:** Visual stats using Chart.js (books per category, recent additions).

---

### 🔒 Security & Audit

* Session-based access control and logout timeout.
* Form validation and input sanitization to prevent SQL injection.
* Audit trail for tracking who created, updated, or deleted records.

---

## 🧱 System Architecture

| Layer    | Technology                                     |
| -------- | ---------------------------------------------- |
| Frontend | HTML5, CSS3, Bootstrap, JavaScript, DataTables |
| Backend  | PHP (Core PHP – procedural or modular)         |
| Database | MySQL                                          |
| Server   | Apache (via XAMPP/WAMP or Plesk)               |

---

## 🚫 Out of Scope

* Payment or e-commerce features.
* Book rental or lending workflows.
* API or mobile app integration (planned for future).

---

## 🧠 Future Enhancements

* REST API endpoints for mobile or React/Vue frontend.
* Book issue/return feature (Library Module).
* Email notifications and password reset.
* Cloud storage (AWS S3) for cover images.
* Advanced analytics dashboard.

---

## ✅ Outcome

By the end of development, this project provides:

* A secure, structured system for managing users and books.
* Scalable modular code ready for further extension.
* A professional foundation for an advanced library or bookstore management system.

---
