<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 2000 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the Laravel [Patreon page](https://patreon.com/taylorotwell).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Cubet Techno Labs](https://cubettech.com)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[Many](https://www.many.co.uk)**
- **[Webdock, Fast VPS Hosting](https://www.webdock.io/en)**
- **[DevSquad](https://devsquad.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[OP.GG](https://op.gg)**
- **[WebReinvent](https://webreinvent.com/?utm_source=laravel&utm_medium=github&utm_campaign=patreon-sponsors)**
- **[Lendio](https://lendio.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Tutorial Run Program

<P> Sistem Gym yang memakai kartu untuk melakukan check in dan check out sebagai M2M utama </p>

Untuk menjalankan program harus menginstall beberapa tools dan migrate Seeder dari paket gym dan superadmin untuk akses awal dan generate api key.
ganti menjadi .env
buat juga database dan konfigurasikan dengan .env 

php artisan composer install
php artisan composer update
php artisan db:seed --class=PaketGym
php artisan db:seed --classSuperAdminSeeder
php artisan app:generate-api-key "Device Admin"
php artisan migrate
php artisan serve


 




POST http://127.0.0.1:8000/api/v1/auth/login 
 ## Header
    accept Application/json
    content/type Application/json

## Body jika menggunakan postman taruh di dalam RAW
## Login SuperAdmin
{
    "name": "Superadmin",
    "email": "superadmin@coboy.com",
    "password": "12345678",
    "role" : "superadmin"
}

setelah login dengan superadmin langkah selanjutnya adalah dengan membuat register admin dengan route api sebagai berikut
POST http://127.0.0.1:8000/api/v1/auth/register/admin
## Header
     accept Application/json
     content/type Application/json 
     Authorization Bearer "Token yang kamu dapat dari hasil login"

## Register Admin 
{
    "name": "admin1",
    "email": "admin1@coboy.com",
    "password": "12345678",
    "password_confirmation": "12345678",
    "role": "admin",
}

Setelah berhasil maka kita bisa login dengan menggunakan admin yang sudah di buat dengan akun superadmin tadi dan route login masih sama dengan route login yang superadmin gunakan
## Login Admin  
POST http://127.0.0.1:8000/api/v1/auth/login 
    header
    accept Application/json
    content/typw Application

## Body
{
    "name": "admin1",
    "email": "admin1@coboy.com",
    "password": "12345678",
    "role" : "admin"
} 

Setelah login dengan akun admin kita bisa melakukan langkah selanjutnya dengan register member yang akan mendaftar dengan langkah sebagai berikut alert!!! "kita hanya bisa mendaftarkan member dengan menggunakan akun admin atau superadmin"

## Register Member

POST http://127.0.0.1:8000/api/v1/auth/register/member
     header 
     accept Application/json
     content/type Application/json 
     Authorization Bearer "Token yang kamu dapat dari hasil login admin"

## Body    
    {
        "name": "member",
        "email": "member@coboy.com",
        "phone": "123456789",  
        "membership_type": "bronze",
        "payment_method": "cash"
    }

Setelah membuat member kita bisa login dengan member dengan akun member yang sudah di buat oleh admin. Allert!!! "password yang di gunakan untuk login member adalah menggunakan 6 digit akhir dari nomer telephone yang di daftarkan oleh member"

## Login Member 
POST http://127.0.0.1:8000/api/v1/auth/login

## Header 
    accept Application/json
    content/type Application/json 
    Authorization Bearer "Token yang kamu dapat dari hasil login admin" 

## Body 
{
    "name": "member",
    "email": "member@coboy.com",
    "password": "456789",
    "role": "member"
}

Setelah login dengan member kita akan mendapatkan kartu member yang sudah kita buat methodnya di MemberController dengan nama RF_id_card
dan kita akan melakukan bagian paling pentig yang akan membuat App ini menggunakan konsep machine to machine dengan tap in kartu agar member bisa di katakan hadir di sesigym. pertama tama kita akan melakukan generate api key untuk akses kartu tersebut ke server dengan langkah. "rfid_card": "RFID-67605F2AC9F9E" itu adalah hasil dari kita mendaftarkan member akan muncul di bagian json
php artisan app:generate-api-key "Device Admin" dengan kita menyimpannya dulu di notepad.

## Check-in Kartu Member 
POST http://127.0.0.1:8000/api/v1/device/check-in
    Header 
    x-api-key wJNnxHjmVrd1BDQHon6SwE7IWHHeuSQs
    X-Requested-With RFID Device Admin
    content/type application/json

## Body
    {
        "rfid_card": "RFID-67605F2AC9F9E"
    }

Setelah kita melakukan check-in kita bisa check-out setelah member menyelesaikan kegiatannya di gym

## Check-out kartu Member
POST http://127.0.0.1:8000/api/v1/device/check-out
## Header 
    x-api-key wJNnxHjmVrd1BDQHon6SwE7IWHHeuSQs
    X-Requested-With RFID Device Admin
    content/type application/json

## Body
    {
        "rfid_card": "RFID-67605F2AC9F9E"
    }
