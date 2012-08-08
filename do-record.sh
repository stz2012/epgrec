#!/bin/bash
echo "CHANNEL : $CHANNEL"
echo "DURATION: $DURATION"
echo "OUTPUT  : $OUTPUT"
echo "TUNER : $TUNER"
echo "TYPE : $TYPE"
echo "MODE : $MODE"
echo "SID  : $SID"

RECORDER1=/usr/local/bin/recfriio
RECORDER2=/usr/local/bin/recfsusb2n

case ${TYPE} in
    "BS"|"CS")
        case ${MODE} in
            0)
                $RECORDER1 --b25 --strip --sync --sid epg $CHANNEL $DURATION ${OUTPUT} >/dev/null
                ;;
            *)
                $RECORDER1 --b25 --strip --sync --sid $SID $CHANNEL $DURATION ${OUTPUT} >/dev/null
                ;;
        esac
        ;;
    *)
        case ${MODE} in
            0)
                $RECORDER2 --b25 --sid epg $CHANNEL $DURATION ${OUTPUT} >/dev/null
                ;;
            *)
                $RECORDER2 --b25 --sid $SID $CHANNEL $DURATION ${OUTPUT} >/dev/null
                ;;
        esac
        ;;
esac
