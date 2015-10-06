#!/bin/bash






function _xtrim()
{
    echo "$*"  | sed -e '~s/^\s*//g' | sed -e '~s/\s*$//g'
}



#RE-ENTRY
RETVAL=9
LUSER=$( _xtrim "${1}" )
LPASS=$( _xtrim "${2}" )

#chk
[[ "" == "${#LUSER}" || "0" == "${#LPASS}" ]] && {
	echo "Invalid Parameters!"
	exit $RETVAL
}

echo "LDAP check for $LUSER"

#exec
/usr/bin/ldapsearch  -x -D "uid=${LUSER},ou=People,dc=shrss,dc=domain" -w "${LPASS}" >/dev/null
LRET=$?


#sanity
if [[ "${LRET}" == "0" ]]
then
	echo "SUCCESS: LDAP signed in okay."
	RETVAL=0
else
	echo "ERROR: LDAP signed in not okay."
	RETVAL=1
fi

#good
exit $RETVAL

