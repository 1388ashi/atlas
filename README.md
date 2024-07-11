## Shopit

#### Merge from shopit
```sql
git pull shopit master --allow-unrelated-histories
```
#### Run supervisor queues
Change  /etc/supervisord.conf:
````
[program:laravel-shopit]
process_name=%(program_name)s_%(process_num)02d
command=/usr/local/php80/bin/php /home/neekshop/domains/api.neekshop.net/public_html/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=atlasmode
numprocs=8
redirect_stderr=true
stdout_logfile=/home/neekshop/domains/api.neekshop.net/public_html/storage/logs/queue.log
stopwaitsecs=3600
````
#### Run this command
`sudo supervisorctl restart laravel-shopit:*`


Shopit is a web applicatio with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

Shopit is accessible, powerful, and provides tools required for large, robust applications.


## Shopit Sponsors

We would like to extend our thanks to the following sponsors for funding Shopit development. If you are interested in becoming a sponsor, please visit the Shetabit [shetabit_page](https://shetabit.com).
## License

The Shopit is not an open-sourced software.
