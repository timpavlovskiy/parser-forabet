#!/usr/bin/env bash

if [ $(dirname $0) = "." ]
then
    scriptPath=$(pwd);
else
   scriptPath=$(dirname $0);
fi

cd ${scriptPath};

go build -o 'loader_fonbet_go_live'
go build -o 'loader_fonbet_go_line'