Containerize This: PHP/Apache/MySQL/KeyCloak
============================================

### Intro
 PHP/Apache/MySQL/KeyCloak have a very large market share on Auth and User management systems and web applications on the internet, and with so many developers using these technologies, there is a lot of interest to modernize the way that they use them from from local development Today we'll take a look at several ways to containerize and link PHP, Apache, KeyCloak and MySQL together while demonstrating some tips, tricks, and best-practices that will help you take a modern approach when developing and deploying  KeyCloak Authaintication to your applications!

Simply copy and paste from this post to replicate the following folder structure. Please note that some Docker and security principals have been skipped here for simplicity and demonstration purposes. Namely PHP using root credentials, hardcoded/weak MySQL password, lack of SSL, to name a few! Do not run this code in production! :-)

```
/php-apache-keycloak-mysql/
.
├── apache
│   ├── demo.apache.conf
│   └── Dockerfile
├── data
│   ├── auto.cnf
│   ├── ca-key.pem
│   ├── ca.pem
│   ├── client-cert.pem
│   ├── client-key.pem
│   ├── ib_buffer_pool
│   ├── ibdata1
│   ├── ib_logfile0
│   ├── ib_logfile1
│   ├── ibtmp1
│   ├── keycloak [error opening dir]
│   ├── mysql [error opening dir]
│   ├── performance_schema [error opening dir]
│   ├── private_key.pem
│   ├── public_key.pem
│   ├── server-cert.pem
│   ├── server-key.pem
│   └── sys [error opening dir]
├── docker-compose.yml
├── keycloak
│   ├── configuration
│   │   └── standalone.xml
│   ├── README.md
│   ├── realm-export.json
│   └── themes
│       ├── dina
│       │   ├── account
│       │   │   ├── resources
│       │   │   │   ├── css
│       │   │   │   │   └── dina.css
│       │   │   │   └── img
│       │   │   │       ├── logo-170.png
│       │   │   │       └── logo.png
│       │   │   └── theme.properties
│       │   ├── admin
│       │   │   ├── resources
│       │   │   │   ├── css
│       │   │   │   │   ├── admin-styles-slave.css
│       │   │   │   │   └── dina.css
│       │   │   │   └── img
│       │   │   │       ├── logo-170.png
│       │   │   │       └── logo.png
│       │   │   └── theme.properties
│       │   └── login
│       │       ├── resources
│       │       │   ├── css
│       │       │   │   └── dina.css
│       │       │   └── img
│       │       │       ├── logo-170.png
│       │       │       └── logo.png
│       │       └── theme.properties
│       └── keycloak_logo.png
├── php
│   └── Dockerfile
└── public_html
    └── index.php
```

Once this structure is replicated or cloned with these files, and Docker installed locally, you can simply run "docker-compose up" from the root of the project to run this entire demo, and point your browser (or curl) to http://localhost:8081 to see the demo. We will get into what "docker-compose" is, and what makes up this basic demonstration in the following sections!

### Docker Compose

This format has been around for a while in Dockerland and is now in version 3.6 at the time of this writing. We'll use 3.2 here to ensure broad compatibility with those who may not be running the latest and greatest versions of Docker (however, you should always upgrade!)

This format allows for defining sets of services which make up an entire application. It allows you to define the dependencies for those services, networks, volumes, etc as code and as you roll into production.

A perfect example is decoupling Apache and PHP by building them out into separate containers. We see our customers often starting to couple Apache and PHP together early on in a Docker journey by building custom images which include both Apache and PHP in the image. This easily works in development scenarios and is a fast way to get off the ground, but as we want to follow a more modern approach of decoupling, we want to break these apart.

The following simple Dockerfiles are what we're using in this example to build a decoupled Apache and PHP envivonment:

#### apache/Dockerfile
```
FROM httpd:2.4.33-alpine

RUN apk update; \
    apk upgrade;

# Copy apache vhost file to proxy php requests to php-fpm container
COPY demo.apache.conf /usr/local/apache2/conf/demo.apache.conf
RUN echo "Include /usr/local/apache2/conf/demo.apache.conf" \
    >> /usr/local/apache2/conf/httpd.conf
```

#### php/Dockerfile
```
FROM php:7.2.7-fpm-alpine3.7

RUN apk update; \
    apk upgrade;

RUN docker-php-ext-install mysqli
```

Note that we run minimal containers wherever possible, in this example we're using official alpine-based images!

### Networking
Now that we have container images for Apache and PHP that are decoupled, how do we get these to interact with eachother? Notice we're using "PHP FPM" for this. We're going to have Apache proxy connections which require PHP rendering to port 9000 of our PHP container, and then have the PHP container serve those out as rendered HTML. Sound complicated? Don't worry! This is very common in modern applications, Apache and NGINX are very good at this proxying, and there is plenty of documentation out there to support this behavior!

Notice in the above Docker Compose example, we link the containers together with an overlay networks we define as "dwproxy_default". By specifying these networks in our services, we can leverage some really cool Docker features! Namely, we can refer to the containers/services by their service names in code, so we don't have to worry about messy hard-coding of IP addresses anymore. Phew! Docker handles this for us by managing and updating /etc/hosts files within containers seamlessly to allow for this cross-talk. We can also enforce which containers can talk to eachother. This is great for production environments!

One note: We need an apache vhost configuration file that is set up to proxy these requests for PHP files to the PHP container. You'll notice in the Dockerfile for Apache we have defined above, we add this file and then include it in the base httpd.conf file. This is for the proxying which allows for the decoupling of Apache and PHP. In this example we called it demo.apache.conf and we have the proxying modules defined as well as the VirtualHost. Also notice that we call the php container in the code "php" which, because of the /etc/hosts file integration that Docker handles for us, works flawlessly!

#### apache/demo.apache.conf
```
ServerName localhost

LoadModule deflate_module /usr/local/apache2/modules/mod_deflate.so
LoadModule proxy_module /usr/local/apache2/modules/mod_proxy.so
LoadModule proxy_fcgi_module /usr/local/apache2/modules/mod_proxy_fcgi.so

<VirtualHost *:80>
    # Proxy .php requests to port 9000 of the php-fpm container
    ProxyPassMatch ^/(.*\.php(/.*)?)$ fcgi://php:9000/var/www/html/$1
    DocumentRoot /var/www/html/
    <Directory /var/www/html/>
        DirectoryIndex index.php
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Send apache logs to stdout and stderr
    CustomLog /proc/self/fd/1 common
    ErrorLog /proc/self/fd/2
</VirtualHost>
```

### Volumes
The last feature that we'll call out here for demonstration purposes is the use of volumes to serve out our code. Both the PHP and Apache containers have access to a "volume" that we define in the docker-compose.yml file which maps the public_html folder of our repository to the respective services for them to access. When we do this, we map a folder on the host filesystem (outside of the container context) to inside of the running containers. Developers may wish to set their projects up like this because it allows them to edit the file outside of the container, yet have the container serve the updated PHP code out as soon as changes are saved.

Volumes are a very powerful construct of the Docker world and we're only scratching the surface of what one can achieve by using them in development and production. Please see the official documentation on volumes for further use cases and best practices!


# URLs used

## locally
* http://keycloak.accounts.dina-web.local:8083
* http://accounts.dina-web.local:8081/ (for testing)


# Setup

## For local development

- Set up the env
- Add these two urls to `/etc/hosts`: `accounts.dina-web.local` and `keycloak.accounts.dina-web.local`
   - 127.0.0.1  accounts.dina-web.local
   - 127.0.0.1  db.accounts.dina-web.local
   - 127.0.0.1  keycloak.accounts.dina-web.local
- Start the services with `make up`
- Configure Keycloak using the Admin console at http://keycloak.accounts.dina-web.local
- Access URLs:
   - Login to the dina realm at http://keycloak.accounts.dina-web.local/auth/realms/dina/account
   - Keycloak Admin Console: http://keycloak.accounts.dina-web.local
   - Demonstration UI with nginx and JavaScript: http://accounts.dina-web.local
   - Demonstration Phpmyadmin : http://db.accounts.dina-web.local
   - Demonstration Auth EndPoints : http://keycloak.accounts.dina-web.local:8083/auth/realms/dina/.well-known/openid-configuration
## For centralized instance

- Add URL(s) to docker-compose.yml
- Create centralized proxy network - `$ docker network create -d bridge dwproxy_default`
- Start the services with `make up`
- Configure Keycloak using the Admin console at http://keycloak.accounts.dina-web.local
- For better performance, enable theme caching (see below)

# Keycloak settings 

Keycloak has a tool to export/import realm settings, but that doesn't seem to work reliably. 
Example export is at keycloak/realm-export.json

**Terms:**

- Realm: an entity that contains all settings for one project, in our case for all of DINA
- Client: an application that uses Keycloak for authentication. E.g. the collections management module

## Basic settings

### dina realm

Create a realm "dina" and enable it. Settings for the realm:

- General
   - Enable
      - User registration 
      - Edit username 
      - Forgot password 
      - Remember Me 
      - Verify email 
      - Login with email 
   - Disable
      - Email as username (due to bug in Keycloak, see above)
   - Require SSL: all requests
- Email
   - Set dmail settings here
- Themes
   - Select dina theme for all services where it it available

This also automatically creates client "account", which is used for managing user's own information on Keycloak.

## Users

For each new user:

- Add user
- Add credential
    - password
    - Temporary: off
- Add role mapping:
    - client role: account (this enables user to login and edit their own info)
    - assigned roles: manage-account, view-profile

## Client

Docker-compose file has a **demo-ui** demonstrating frontent application authenticating with Keycloak. To enable this:

- Uncomment the demo-client and start it with docker-compose
- Add `accounts.dina-web.local` to `/etc/hosts`
- Add matching client to Keycloak:
    - Client ID: dina-accounts-demo
    - Name: Keycloak authentication demo
    - Root URL: http://accounts.dina-web.local
    - Valid redirect URI's: http://accounts.dina-web.local/*
    - Base URL: http://accounts.dina-web.local
    - Web Origins: * (stricter value should be ok also)
- Add permissions for a user to the client
- Access the demo at http://accounts.dina-web.local

## Possible additional settings later

**If users apart from superuser need permissions to add / modify users**, this could be done by creating a group that has permissions to do this:

- Add group "dina-user-admin-group". Add role mappings for Client role "realm-management": manage-user, view-users
- Add user to the group "dina-user-admin-group"


## Theme caching

For developing the themes, disable caching in keycloak/configuration/standalone.xml like so:

        <staticMaxAge>-1</staticMaxAge>
        <cacheThemes>false</cacheThemes>
        <cacheTemplates>false</cacheTemplates>

For production, ensable caching:

        <staticMaxAge>2592000</staticMaxAge>
        <cacheThemes>true</cacheThemes>
        <cacheTemplates>true</cacheTemplates>




### Demonstration of docker-compose up!

```
$ docker-compose up

<... some details omitted ...>
php_1     | [16-Jul-2018 02:08:11] NOTICE: fpm is running, pid 1
php_1     | [16-Jul-2018 02:08:11] NOTICE: ready to handle connections
apache_1  | [Mon Jul 16 02:08:12.494294 2018] [pid 1:tid 140290664872840] AH00489: Apache/2.4.33 (Unix)
apache_1  | [Mon Jul 16 02:08:12.496833 2018] [pid 1:tid 140290664872840] AH00094: Command line: 'httpd -D FOREGROUND'
mysql_1   | 2018-07-16 02:08:12 1 [Note] mysqld: ready for connections.
mysql_1   | Version: '5.6.40'  socket: '/var/run/mysqld/mysqld.sock'  port: 3306  MySQL Community Server (GPL)
```
Notice how these 3 daemons run on PID 1 inside of each container, this is considered a best-practice for building containers!

```
$  curl localhost:8081
Hello Cloudreach!
Attempting MySQL connection from php...
Connected to MySQL successfully!
```


### Conclusion

With these basic principals you can link services together to create applications. You could easily include "composer" in the PHP container to build and run your PHP/Laravel application in a similar manner. Perhaps you want to run Drupal or Wordpress and decouple PHP from the Apache instance, that is possible too! You can even use this to seamlessly test PHP version or MySQL version upgrades with minimal code change. There are a lot of benefits to modernizing your application with Docker using docker-compose and some of the latest images and features.
