#!/bin/bash

export LANG=ja_JP.UTF-8
SCRIPT_DIR=$(cd $(dirname $0);pwd)
SCRIPT_FNAME=`basename $0`
FORCE_FLG=0

# オプションを処理
while getopts f OPT
do
  case $OPT in
    f) FORCE_FLG=1 ;;
  esac
done

${SCRIPT_DIR}/check_halt.php > /dev/null 2>&1
if [ $? -eq 0 ] || [ $FORCE_FLG -eq 1 ]; then
	# ログイン人数チェック
	if [ `who | grep 'pts\/\|tty\/' | wc -l` -eq 0 ] || [ $FORCE_FLG -eq 1 ]; then
		# Sambaアクセスチェック
		if [ `smbstatus -L | wc -l` -eq 2 ] || [ $FORCE_FLG -eq 1 ]; then
			# 次回起動時間設定
			${SCRIPT_DIR}/check_hibernate.php > /dev/null 2>&1
			if [ $? -eq 0 ]; then
				${SCRIPT_DIR}/cli_logger.php shutdown "${SCRIPT_FNAME} 自動終了"
				/sbin/shutdown -h now
			fi
		else
			${SCRIPT_DIR}/cli_logger.php chkstatus "${SCRIPT_FNAME} ユーザがSambaにアクセス中"
		fi
	else
		${SCRIPT_DIR}/cli_logger.php chkstatus "${SCRIPT_FNAME} ユーザがログイン中"
	fi
fi
