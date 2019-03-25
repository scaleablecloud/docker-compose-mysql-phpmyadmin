# Start mysql database server with phpmyadmin using docker compose 

* Clone the repo

git clone git@github.com:khawarhere/mysql-phpmyadmin-docker-compose.git

* cd into root folder  

* Buid and run docker compose to make db live

`docker-compose up`

* To start phpmyadmin open in browser

http://localhost:83

<!-- 


 -->

 docker network create -d bridge dwproxy_default

sudo mkdir -p /var/www/rasauth.scaleablecloud.com/html
sudo chown -R $USER:$USER /var/www/rasauth.scaleablecloud.com/html
sudo chmod -R 755 /var/www/rasauth.scaleablecloud.com
nano /var/www/rasauth.scaleablecloud.com/html/index.html


<html>
    <head>
        <title>Welcome to rasauth.scaleablecloud.com!</title>
    </head>
    <body>
        <h1>Success!  The rasauth.scaleablecloud.com server block is working!</h1>
    </body>
</html>

sudo nano /etc/apache2/sites-available/rasauth.scaleablecloud.com.conf

<VirtualHost *:80>
    ServerAdmin khawarhere@gmail.com
    ServerName rasauth.scaleablecloud.com
    ServerAlias www.rasauth.scaleablecloud.com
    DocumentRoot /var/www/rasauth.scaleablecloud.com/html
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>

sudo a2ensite rasauth.scaleablecloud.com.conf
sudo a2dissite 000-default.conf
sudo apache2ctl configtest
sudo systemctl restart apache2

<!-- SSL -->

sudo nano /etc/apache2/sites-available/rasauth.scaleablecloud.com.conf

sudo certbot --apache -d rasauth.scaleablecloud.com
