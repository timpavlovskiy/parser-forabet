#!/usr/bin/env bash

if [ $(dirname $0) = "." ]
then
    scriptPath=$(pwd);
else
   scriptPath=$(dirname $0);
fi

echo "ScriptPath: ${scriptPath}";


# LOADER LIVE
loaderLiveInterval=2;
loaderLiveLog=${scriptPath}/loaderLive.log;
loaderLive=${scriptPath}/loaderLive.php;

# LOADER LINE
loaderLineInterval=15;
loaderLineLog=${scriptPath}/loaderLine.log;
loaderLine=${scriptPath}/loaderLine.php;

# RESULTS LIVE
resultLiveInterval=5;
resultLiveLog=${scriptPath}/resultLive.log;
resultLive=${scriptPath}/result/live.php;

# RESULTS ALL
resultAllInterval=60;
resultAllLog=${scriptPath}/resultAll.log;
resultAll=${scriptPath}/result/all.php;

# PARSER
parserInterval=15;
parserLog=${scriptPath}/parser.log;
parser=${scriptPath}/parser.php;

# UPDATER LIVE
converterLiveInterval=1;
converterLiveLog=${scriptPath}/updaterLive.log;
converterLive=${scriptPath}/updaterLive.php;

# UPDATER LINE
converterLineInterval=15;
converterLineLog=${scriptPath}/updaterLine.log;
converterLine=${scriptPath}/updaterLine.php;

# VIDEO
videoInterval=5;
videoLog=${scriptPath}/video.log;
video=${scriptPath}/video/live.php;

# CLEAR LOG
clearLogInterval=43200; # 12 hours
clearLog=${scriptPath}/logger/clear.php;

# LANGUAGE LIVE UPDATER
languageUpdaterLiveInterval=40;
languageUpdaterLiveLog=${scriptPath}/languageUpdaterLive.log;
languageUpdaterLive="${scriptPath}/language.php";

# LOADER GO CLEAR LOG
loaderGoClearLogInterval=43200; # 12 hours

# LOADER GO LIVE
loaderGoLiveInterval=10;
loaderGoLiveLog=${scriptPath}/loaderGoLive.log;
loaderGoLive=${scriptPath}/loader-go-live-run.sh;

# LOADER GO LINE
loaderGoLineInterval=20;
loaderGoLineLog=${scriptPath}/loaderGoLine.log;
loaderGoLine=${scriptPath}/loader-go-line-run.sh;

# LOADER GO UPDATE PROXY
loaderGoUpdateProxyInterval=60;
loaderGoUpdateProxyLog=${scriptPath}/loaderGoUpdateProxy.log;
loaderGoUpdateProxy=${scriptPath}/loader-go-update-proxy.php;

counter=1;
while true
	do
        # BOOTSTRAP
        if [ $((counter)) = 1 ]; then
            echo "[$(date)] Loader Live";
            php ${loaderLive} >> ${loaderLiveLog} 2>&1 &

            echo "[$(date)] Loader Line";
            php ${loaderLine} >> ${loaderLineLog} 2>&1 &

            echo "[$(date)] Results Live";
            php ${resultLive} >> ${resultLiveLog} 2>&1 &

            echo "[$(date)] Results All";
            php ${resultAll} >> ${resultAllLog} 2>&1 &

            echo "[$(date)] Parser";
            php ${parser} >> ${parserLog} 2>&1 &

            echo "[$(date)] Updater Live";
            php ${converterLive} >> ${converterLiveLog} 2>&1 &

            echo "[$(date)] Updater Line";
            php ${converterLine} >> ${converterLineLog} 2>&1 &

            echo "[$(date)] Video";
            php ${video} >> ${videoLog} 2>&1 &

            echo "[$(date)] Clear Log";
            php ${clearLog} >> /dev/null 2>&1 &

            echo "[$(date)] Language Updater Live";
            php ${languageUpdaterLive} >> ${languageUpdaterLiveLog} 2>&1 &

             echo "[$(date)] Loader Go Clear Log";
             cat /dev/null > ${loaderGoLiveLog} &
             cat /dev/null > ${loaderGoLineLog} &
             cat /dev/null > ${loaderGoUpdateProxyLog} &

             echo "[$(date)] Loader Go Live";
             sh ${loaderGoLive} >> ${loaderGoLiveLog} 2>&1 &

             echo "[$(date)] Loader Go Line";
             sh ${loaderGoLine} >> ${loaderGoLineLog} 2>&1 &

             echo "[$(date)] Loader Update Proxy";
             php ${loaderGoUpdateProxy} >> ${loaderGoUpdateProxyLog} 2>&1 &
        fi


        # LOADER LIVE
        if [ $((counter % loaderLiveInterval)) = 0 ]; then
            if [ -z "$(pgrep -f ${loaderLive})" ]; then
                echo "[$(date)] Loader Live";
                php ${loaderLive} >> ${loaderLiveLog} 2>&1 &
            fi
        fi


        # LOADER LINE
        if [ $((counter % loaderLineInterval)) = 0 ]; then
            if [ -z "$(pgrep -f ${loaderLine})" ]; then
                echo "[$(date)] Loader Line";
                php ${loaderLine} >> ${loaderLineLog} 2>&1 &
            fi
        fi


        # RESULTS
        if [ $((counter % resultLiveInterval)) = 0 ]; then
            if [ -z "$(pgrep -f ${resultLive})" ]; then
                echo "[$(date)] Results Live";
                php ${resultLive} >> ${resultLiveLog} 2>&1 &
            fi
        fi

        if [ $((counter % resultAllInterval)) = 0 ]; then
            if [ -z "$(pgrep -f ${resultAll})" ]; then
                echo "[$(date)] Results All";
                php ${resultAll} >> ${resultAllLog} 2>&1 &
            fi
        fi


        # PARSER
        if [ $((counter % parserInterval)) = 0 ]; then
            if [ -z "$(pgrep -f ${parser})" ]; then
                echo "[$(date)] Parser";
                php ${parser} >> ${parserLog} 2>&1 &
            fi
        fi


        # UPDATER LIVE
        if [ $((counter % converterLiveInterval)) = 0 ]; then
            if [ -z "$(pgrep -f ${converterLive})" ]; then
                echo "[$(date)] Updater Live";
                php ${converterLive} >> ${converterLiveLog} 2>&1 &
            fi
        fi


        # UPDATER LINE
        if [ $((counter % converterLineInterval)) = 0 ]; then
            if [ -z "$(pgrep -f ${converterLine})" ]; then
                echo "[$(date)] Updater Line";
                php ${converterLine} >> ${converterLineLog} 2>&1 &
            fi
        fi


        # VIDEO
        if [ $((counter % videoInterval)) = 0 ]; then
            if [ -z "$(pgrep -f ${video})" ]; then
                echo "[$(date)] Video";
                php ${video} >> ${videoLog} 2>&1 &
            fi
        fi


       # CLEAR LOG
        if [ $((counter % clearLogInterval)) = 0 ]; then
            if [ -z "$(pgrep -f ${clearLog})" ]; then
                echo "[$(date)] Clear Log";
                php ${clearLog} >> /dev/null 2>&1 &
            fi
        fi

         # LOADER GO CLEAR LOG
         if [ $((counter % loaderGoClearLogInterval)) = 0 ]; then
             echo "[$(date)] Loader Go Clear Log"
             cat /dev/null > ${loaderGoLiveLog} &
             cat /dev/null > ${loaderGoLineLog} &
             cat /dev/null > ${loaderGoUpdateProxyLog} &
         fi

         # LOADER GO LIVE
         if [ $((counter % loaderGoLiveInterval)) = 0 ]; then
             if [ -z "$(pgrep -f ${loaderGoLive})" ]; then
                 echo "[$(date)] Loader Go Live ";
                 sh ${loaderGoLive} >> ${loaderGoLiveLog} 2>&1 &
             fi
         fi

         # LOADER GO LINE
         if [ $((counter % loaderGoLineInterval)) = 0 ]; then
             if [ -z "$(pgrep -f ${loaderGoLine})" ]; then
                 echo "[$(date)] Loader Go Line ";
                 sh ${loaderGoLine} >> ${loaderGoLineLog} 2>&1 &
             fi
         fi

         # LOADER GO UPDATE PROXY
         if [ $((counter % loaderGoUpdateProxyInterval)) = 0 ]; then
             if [ -z "$(pgrep -f ${loaderGoUpdateProxy})" ]; then
                 echo "[$(date)] Loader Go Update Proxy ";
                 php ${loaderGoUpdateProxy} >> ${loaderGoUpdateProxyLog} 2>&1 &
             fi
                fi

         # LANGUAGE LIVE UPDATER
        if [ $((counter % languageUpdaterLiveInterval)) = 0 ]; then
            if [ -z "$(pgrep -f "${languageUpdaterLive}")" ]; then
                echo "[$(date)] Language Updater Live";
                php ${languageUpdaterLive} >> ${languageUpdaterLiveLog} 2>&1 &
            fi
        fi


    counter=$((counter + 1));
    sleep 1;
	done