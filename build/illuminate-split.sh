git subsplit init git@github.com:laravel/framework.git
git subsplit publish --heads="master 5.0 4.2" --no-tags src/Illuminate/Auth:git@github.com:illuminate/auth.git
git subsplit publish --heads="master 5.0" --no-tags src/Illuminate/Bus:git@github.com:illuminate/bus.git
git subsplit publish --heads="master 5.0 4.2" --no-tags src/Illuminate/Cache:git@github.com:illuminate/cache.git
git subsplit publish --heads="master 5.0 4.2" --no-tags src/Illuminate/Config:git@github.com:illuminate/config.git
git subsplit publish --heads="master 5.0 4.2" --no-tags src/Illuminate/Console:git@github.com:illuminate/console.git
git subsplit publish --heads="master 5.0 4.2" --no-tags src/Illuminate/Container:git@github.com:illuminate/container.git
git subsplit publish --heads="master 5.0" --no-tags src/Illuminate/Contracts:git@github.com:illuminate/contracts.git
git subsplit publish --heads="master 5.0 4.2" --no-tags src/Illuminate/Cookie:git@github.com:illuminate/cookie.git
git subsplit publish --heads="master 5.0 4.2" --no-tags src/Illuminate/Database:git@github.com:illuminate/database.git
git subsplit publish --heads="master 5.0 4.2" --no-tags src/Illuminate/Encryption:git@github.com:illuminate/encryption.git
git subsplit publish --heads="master 5.0 4.2" --no-tags src/Illuminate/Events:git@github.com:illuminate/events.git
git subsplit publish --heads="4.2" --no-tags src/Illuminate/Exception:git@github.com:illuminate/exception.git
git subsplit publish --heads="master 5.0 4.2" --no-tags src/Illuminate/Filesystem:git@github.com:illuminate/filesystem.git
git subsplit publish --heads="master 5.0 4.2" --no-tags src/Illuminate/Hashing:git@github.com:illuminate/hashing.git
git subsplit publish --heads="master 5.0 4.2" --no-tags src/Illuminate/Http:git@github.com:illuminate/http.git
git subsplit publish --heads="master 5.0 4.2" --no-tags src/Illuminate/Log:git@github.com:illuminate/log.git
git subsplit publish --heads="master 5.0 4.2" --no-tags src/Illuminate/Mail:git@github.com:illuminate/mail.git
git subsplit publish --heads="master 5.0 4.2" --no-tags src/Illuminate/Pagination:git@github.com:illuminate/pagination.git
git subsplit publish --heads="master 5.0" --no-tags src/Illuminate/Pipeline:git@github.com:illuminate/pipeline.git
git subsplit publish --heads="master 5.0 4.2" --no-tags src/Illuminate/Queue:git@github.com:illuminate/queue.git
git subsplit publish --heads="master 5.0 4.2" --no-tags src/Illuminate/Redis:git@github.com:illuminate/redis.git
git subsplit publish --heads="master 5.0 4.2" --no-tags src/Illuminate/Routing:git@github.com:illuminate/routing.git
git subsplit publish --heads="master 5.0 4.2" --no-tags src/Illuminate/Session:git@github.com:illuminate/session.git
git subsplit publish --heads="master 5.0 4.2" --no-tags src/Illuminate/Support:git@github.com:illuminate/support.git
git subsplit publish --heads="master 5.0 4.2" --no-tags src/Illuminate/Translation:git@github.com:illuminate/translation.git
git subsplit publish --heads="master 5.0 4.2" --no-tags src/Illuminate/Validation:git@github.com:illuminate/validation.git
git subsplit publish --heads="master 5.0 4.2" --no-tags src/Illuminate/View:git@github.com:illuminate/view.git
rm -rf .subsplit/
