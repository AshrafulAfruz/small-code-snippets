# Redis Persistence Setup

redis-cli
AUTH redis_password

#configure AOF 
## This enables AOF without restarting and without losing data. 
## The CONFIG REWRITE command saves the changes to redis.conf file so they persist across restarts
CONFIG SET appendonly yes
CONFIG SET appendfsync everysec
CONFIG REWRITE 
BGREWRITEAOF  #starting rewrite manually.

# Recommended production settings
CONFIG SET auto-aof-rewrite-percentage 100
CONFIG SET auto-aof-rewrite-min-size 64mb

# RDB snapshot
BGSAVE #manually save now
LASTSAVE #Unix timestamp of last successful save

# for check 
INFO persistence
CONFIG GET save # RDB configured or not
CONFIG GET dir
CONFIG GET appendonly #AOF configured or not
CONFIG GET auto-aof-rewrite-percentage
CONFIG GET auto-aof-rewrite-min-size

# Check if dump files exist
sudo ls -lh /var/lib/redis/
