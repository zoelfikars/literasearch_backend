
tutor install
composer install

copy .env.example to .env setup .env
SUPER_ADMIN_EMAIL=youradminemail@domain.com
SUPER_ADMIN_PASSWORD=youradminpassword

php artisan generate:key
php artisan install:api
php artisan migrate:fresh
php artisan db:seed
no


setup python

bikin venv #python -m venv venv_easyocr 
activate #./venv_easyocr/Scripts/activate

libraries :
pip install easyocr opencv-python numpy
