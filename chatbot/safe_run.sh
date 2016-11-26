#!/bin/sh

DIR=`dirname $0`

while [ true ]; do
    php $DIR/run.php
    echo "Hard reset of chat bot!"
    sleep 30
done

