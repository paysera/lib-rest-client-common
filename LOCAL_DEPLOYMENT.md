## Local deployment for development and/or running tests

1. Run:
- cp .docker/supervisor.conf.dist .docker/supervisor.conf
- cp .dist.env .env

Set environment values inside the `.env`.\
Also configure `supervisor`'s options if you need. By default no services autostart but that's enough to run tests.
\
Then:
- docker-compose build
- docker-compose up -d --remove-orphans

2. Check:
- docker ps | grep lib-rest-client-common\
You should see an output similar to this:\
`
1a7b71f2d8f9   lib-rest-client-common-lib   "/usr/bin/supervisord"   21 minutes ago   Up 21 minutes   rest-client-common
`
3. Enter the container:\
`docker exec -it lib-rest-client-common bash`\
and run inside:\
`composer i`

5. Run tests:\
`bin/phpunit`
6. Enjoy your work!
