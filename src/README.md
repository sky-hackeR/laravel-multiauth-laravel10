# Laravel Multi-Auth (Laravel 10+) — Experimental

⚠️ **Status: Experimental / Proof of Concept**

This package is an **experimental multi-authentication scaffolder** for Laravel 10+, 
created as a learning and exploration project around Laravel authentication internals.

It is published on Packagist for educational and prototyping purposes and **is not recommended for production use**.

Originally forked and upgraded from  
[alaminfirdows/laravel-multi-auth](https://github.com/alaminfirdows/laravel-multi-auth).

---

## 🎯 Purpose of This Package

This project was built to explore and demonstrate:

- Laravel authentication guards
- Dynamic modification of `config/auth.php`
- Artisan command development
- Code scaffolding (models, migrations, routes, controllers)
- Package structure and Packagist publishing

---

## ✨ Features (Experimental)

- Scaffold multiple authentication guards (e.g. Admin, Teacher, Manager)
- Auto-generates:
  - Models (`App\Models\{Guard}`)
  - Migrations
  - Controllers
  - Route files (`routes/{guard}.php`)
- Updates authentication configuration
- Custom Artisan command

> ⚠️ Behaviour may be unstable across Laravel versions.

---

## ⚙️ Installation (For Testing / Learning Only) 

`composer require skyhacker/laravel-multi-auth`  

  

## 🚀 Usage Example 

`php artisan laravel-multi-auth:install Admin -f`

  

This attempts to scaffold:

*   Admin model
    
*   Admin migration
    
*   Admin routes
    
*   Admin authentication controllers
    

* * *

## 🧪 Stability Notice

This package:

*   May break on newer Laravel updates
    
*   Has not been battle-tested in production
    
*   Is intended as a learning artifact, not a drop-in solution
    

If you need production-ready multi-auth, consider:

*   Laravel Fortify
    
*   Laravel Breeze with multiple guards
    
*   Custom guard implementations
    

* * *

## 📦 Packagist

This package is published on Packagist to demonstrate:

*   Composer package creation
    
*   Semantic versioning
    
*   Open-source documentation practices
    

* * *

## 🧠 Lessons Learned

Through this project, I gained hands-on experience with:

*   Laravel authentication internals
    
*   Artisan command development
    
*   Package publishing workflows
    
*   API design trade-offs
    

* * *

## 🤝 Contributions

This repository is currently not actively maintained.  
Feel free to fork or experiment for learning purposes.