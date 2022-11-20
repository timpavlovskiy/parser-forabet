<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/ProxyRepository.php';

$redis = getRedis();
$proxyRepository = new \ProxyRepository(getPDO(),[PARSER_LIVE_ID,PARSER_LINE_ID]);

if(GOLANG_LOADER_LIVE_ENABLE){
    $liveProxies = $proxyRepository->findProxiesByParserId(PARSER_LIVE_ID);
    $redis->publish(GOLANG_LOADER_LIVE_PUB_SUB_PROXIES_KEY,json_encode($liveProxies));
    $failedProxies = $redis->get(GOLANG_LOADER_LIVE_FAILED_PROXIES_KEY);
    $failedProxies = json_decode($failedProxies,true);
    if(is_array($failedProxies)){
        $proxyRepository->updateFailedProxies(PARSER_LIVE_ID,$failedProxies);
        $redis->set(GOLANG_LOADER_LIVE_FAILED_PROXIES_KEY,'[]');
    }
}

if(GOLANG_LOADER_LINE_ENABLE){
    $lineProxies = $proxyRepository->findProxiesByParserId(PARSER_LINE_ID);
    $redis->publish(GOLANG_LOADER_LINE_PUB_SUB_PROXIES_KEY,json_encode($lineProxies));
    $failedProxies = $redis->get(GOLANG_LOADER_LINE_FAILED_PROXIES_KEY);
    $failedProxies = json_decode($failedProxies,true);
    if(is_array($failedProxies)){
        $proxyRepository->updateFailedProxies(PARSER_LINE_ID,$failedProxies);
        $redis->set(GOLANG_LOADER_LINE_FAILED_PROXIES_KEY,'[]');
    }
}

