#!/bin/sh

if [ $(dirname $0) = "." ]
then
    scriptPath=$(pwd);
else
   scriptPath=$(dirname $0);
fi


scriptPID=$(pgrep -f loader_fonbet_go_line);

echo "scriptPath - ${scriptPath}";

if [ -z "${scriptPID}" ]
then
    echo "No scripts running";
else
    echo "Stopped scrips";
    kill -9 ${scriptPID}
fi