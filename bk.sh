#!/bin/bash





fdir="/var/www/html/backup"
fname=$fdir/src-backup-multichannel-api-$(date '+%Y-%m-%d_%H%M%S')-$$-$RANDOM.tar.gz

[[ ! -d "${fdir}" ]] && {
        mkdir -p ${fdir} 2>/dev/null
}


echo $fname

tar cvfz $fname ../api/


echo $fname
exit
