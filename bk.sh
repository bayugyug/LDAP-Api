#!/bin/bash





fdir="/var/www/html/backup"
fname=$fdir/src-backup-rccl-api-$(date '+%Y-%m-%d_%H%M%S')-$$-$RANDOM.tar.gz

[[ ! -d "${fdir}" ]] && {
        mkdir -p ${fdir} 2>/dev/null
}


echo $fname

rm -f log/* 2>/dev/null

tar cvfz $fname ../api/


echo $fname
exit
