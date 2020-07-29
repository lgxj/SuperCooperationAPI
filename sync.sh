#不要删除vendor目录，避免composer update耗时，更改composer.json，需手动composer update  --no-dev
rm -rf /var/www/SuperCooperationApi/app
rm -rf /var/www/SuperCooperationApi/routes
rm -rf /var/www/SuperCooperationApi/resources
rm -rf /var/www/SuperCooperationApi/config
rm -rf /var/www/SuperCooperationApi/databaes
rm -rf /var/www/SuperCooperationApi/public
\cp -rfv /home/vsftpd/suqian/SuperCooperationApi/app /var/www/SuperCooperationApi/app
\cp -rfv /home/vsftpd/suqian/SuperCooperationApi/config /var/www/SuperCooperationApi/config
\cp -rfv /home/vsftpd/suqian/SuperCooperationApi/resources /var/www/SuperCooperationApi/resources
\cp -rfv /home/vsftpd/suqian/SuperCooperationApi/database /var/www/SuperCooperationApi/databaes
\cp -rfv /home/vsftpd/suqian/SuperCooperationApi/public /var/www/SuperCooperationApi/public
\cp -rfv /home/vsftpd/suqian/SuperCooperationApi/routes /var/www/SuperCooperationApi/routes

\cp -fv /home/vsftpd/suqian/SuperCooperationApi/.env.prod  /var/www/SuperCooperationApi/.env
\cp -fv /home/vsftpd/suqian/SuperCooperationApi/server.php  /var/www/SuperCooperationApi/server.php
\cp -fv /home/vsftpd/suqian/SuperCooperationApi/composer.json  /var/www/SuperCooperationApi/composer.json
\cp -fv /home/vsftpd/suqian/SuperCooperationApi/package.json  /var/www/SuperCooperationApi/package.json
\cp -fv /home/vsftpd/suqian/SuperCooperationApi/artisan  /var/www/SuperCooperationApi/artisan
\cp -fv /home/vsftpd/suqian/SuperCooperationApi/clean.sh  /var/www/SuperCooperationApi/clean.sh
chmod 755 -R /var/www/SuperCooperationApi/
chmod 777 -R /var/www/SuperCooperationApi/storage/
cd /var/www/SuperCooperationApi/
php /var/www/SuperCooperationApi/artisan  route:cache
php /var/www/SuperCooperationApi/artisan  config:clear
php /var/www/SuperCooperationApi/artisan  view:clear
#supervisorctl restart all
#composer update --no-dev
cd /home/vsftpd/suqian/SuperCooperationApi
chmod +x /var/www/SuperCooperationApi/clean.sh
