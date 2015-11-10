#!/bin/bash

export LANG=ja_JP.UTF-8
SCRIPT_DIR=$(cd $(dirname $0);pwd)
SCRIPT_FNAME=`basename $0`
LOCK_FILE=/var/lock/subsys/auto_getepg

# 取得状態チェック
${SCRIPT_DIR}/check_getepg.php > /dev/null 2>&1
if [ $? -ne 0 ]; then
    exit
fi

# ロックファイル確認
if [ -e ${LOCK_FILE} ] ; then
   exit
else
   touch ${LOCK_FILE}
fi

${SCRIPT_DIR}/cli_logger.php recorder \
    "${SCRIPT_FNAME} EPGデータ取得開始"

${SCRIPT_DIR}/getEpg.php &
sleep 3
PID=`ps -ef | grep -v grep | grep "getEpg.php" | awk '{print $2}'`
${SCRIPT_DIR}/cli_logger.php recorder \
    "${SCRIPT_FNAME} PID=${PID} の終了待機中"
while [[ ( -d /proc/$PID ) && ( -z `grep zombie /proc/$PID/status` ) ]]
do
    sleep 1
done

${SCRIPT_DIR}/cli_logger.php recorder \
    "${SCRIPT_FNAME} EPGデータ取得終了"

# ロックファイル削除
rm -f ${LOCK_FILE}
