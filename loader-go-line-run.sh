#!/usr/bin/env bash

if [ $(dirname $0) = "." ]
then
    scriptPath=$(pwd);
else
   scriptPath=$(dirname $0);
fi

echo "scriptPath - ${scriptPath}";

scriptName=${scriptPath}/loader_fonbet_go/loader_fonbet_go_line;
${scriptName}  loadEvents \
 --httpHeaders '{"Accept":"text\/html,application\/xhtml+xml,application\/xml;q=0.9,image\/avif,image\/webp,image\/apng,*\/*;q=0.8,application\/signed-exchange;v=b3;q=0.9","Accept-Encoding":"gzip, deflate, br","Accept-Language":"ru-BY,ru;q=0.9","Cache-Control":"max-age=0","Connection":"keep-alive","Host":"line03.by0e87-resources.by","sec-ch-ua":"\" Not A;Brand\";v=\"99\", \"Chromium\";v=\"99\", \"Google Chrome\";v=\"99\"","sec-ch-ua-mobile":"?0","sec-ch-ua-platform":"Linux","Sec-Fetch-Dest":"document","Sec-Fetch-Mode":"navigate","Sec-Fetch-Site":"none","Sec-Fetch-User":"?1","Upgrade-Insecure-Requests":"1","User-Agent":"Mozilla\/5.0 (X11; Linux x86_64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/50.0.2661.86 Safari\/537.36"}' \
 --httpTimeout 3 \
 --countAttemptsLoad 2 \
 --templateUrlEvent 'https://line03.by0e87-resources.by/events/event?lang=ru&eventId=[[eventId]]&scopeMarket=700&version=0' \
 --redisHostEnvName 'REDIS_HOST' \
 --redisPortEnvName 'REDIS_PORT' \
 --redisSelectDb 4 \
 --redisPrefix '1001:' \
 --redisPrefixForEvent 'PRS:FB:LINE' \
 --redisEventKeyTTL 300 \
 --redisEventIdsChannel 'PRS:FB:LINE:LOADER_GO:EVENT_IDS' \
 --redisProxyChannel 'PRS:FB:LINE:LOADER_GO:PROXIES' \
 --redisKeyForFailedProxies 'PRS:FB:LINE:LOADER_GO:FAILED_PROXIES' \
 --countHTTPWorkers 50 \
 --updateEventDuration 6000 \
 --maxConnections 100