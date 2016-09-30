#!/bin/bash
ROOTDIR=/Users/eric/Work/hymnal.net/code
hymntype=$1
hymnstartnum=$2
hymnendnum=$3
debug=$4
saveFile=$5
force=$6
if [ -z $hymntype ] || [ -z $hymnstartnum ]; then
	echo 'Usage: genpg <type> <start num> <end num> <debug=true|false> <saveFile=true|false> <force=true|false>'
	exit
fi
if [ -z $hymnendnum ]; then
	hymnendnum=$hymnstartnum
fi
if [ -z $debug ]; then
	debug='false'
fi
if [ -z $saveFile ]; then
	saveFile='false'
fi
if [ -z $force ]; then
	force='false'
fi

hymnstartsuffix=`echo "$hymnstartnum" | sed "s/0*\([0-9]*\)\([a-z]*\)/\2/"`
hymnstartnum=`echo "$hymnstartnum" | sed "s/0*\([0-9]*\)\([a-z]*\)/\1/"`
hymnendsuffix=`echo "$hymnendnum" | sed "s/0*\([0-9]*\)\([a-z]*\)/\2/"`
hymnendnum=`echo "$hymnendnum" | sed "s/0*\([0-9]*\)\([a-z]*\)/\1/"`

dir=''
case $hymntype in
	h)
		dir="$ROOTDIR/resources/Hymnal/English";;
	ch)
		dir="$ROOTDIR/resources/Hymnal/Chinese";;
	ts)
		dir="$ROOTDIR/resources/Hymnal/ChineseTS";;
	de)
		dir="$ROOTDIR/resources/Hymnal/German";;
	hr)
		dir="$ROOTDIR/resources/Hymnal/Russian";;
	c)
		dir="$ROOTDIR/resources/Children";;
	lb)
		dir="$ROOTDIR/resources/LongBeach";;
	ns)
		dir="$ROOTDIR/resources/NewSongs";;
	nt)
		dir="$ROOTDIR/resources/NewTunes";;
	m)
		dir="$ROOTDIR/resources/Miscellaneous";;
esac

if [ $hymnstartnum == $hymnendnum ]; then
    files=`ls -a $dir/*$hymnstartnum*.xml`
else
    files=`ls -a $dir/*.xml`
fi

count=0
for filename in $files
do
	filename=`echo "$filename" | sed s#$dir/##g`
	num=`echo "$filename" | sed "s/[a-z]*0*\([0-9]*\)\([a-z]*\).xml/\1/"`
	if [ -z "$filename" ] || [ -z "$num" ]; then
		continue
	fi
	suffix=`echo "$filename" | sed "s/[a-z]*0*\([0-9]*\)\([a-z]*\).xml/\2/"`

	if [ "$num" -lt "$hymnstartnum" ]; then
		continue
	fi

	if [ "$num" -gt "$hymnendnum" ]; then
		break
	fi

	if [ ! -z "$hymnstartsuffix" ]; then
		if [ "$num" -eq "$hymnstartnum" ]; then
			if [ -z "$suffix" ]; then
				continue
			elif [ "$suffix" != "$hymnstartsuffix" ]; then
				continue
			fi
		fi
	fi

    if [ "$hymntype" == "m" ]; then
        realnum=$hymnstartnum
    else
	    realnum=$num
        if [ ! -z "$suffix" ]; then
            realnum="$realnum$suffix"
        fi
    fi

#	echo "filename=$filename, NUM=$num, SUFFIX=$suffix, start=$hymnstartnum, end=$hymnendnum"

	echo 'GENERATING THE PIANO VERSION...'
	hasDiff=1
	if $ROOTDIR/resources/scripts/lilypondgen.sh p $hymntype $realnum false $debug $saveFile $force; then
	    echo ''
		hasDiff=0
    fi

	echo 'GENERATING THE GUITAR VERSION...'
    if $ROOTDIR/resources/scripts/lilypondgen.sh g $hymntype $realnum false $debug $saveFile $force;  then
	    echo ''
	    if [ "$hasDiff" == "0" ]; then
	        count=$((count-1))
	    fi
    else
        echo 'GENERATING THE GUITAR TEXT VERSION...'
        $ROOTDIR/resources/scripts/lilypondgen.sh gt $hymntype $realnum false $debug $saveFile $force

        # Generate Simplified Chinese lead sheets if applicable
        if [ "$hymntype" == "ch" ] || [ "$hymntype" == "ts" ] || [ "$suffix" == "c" ]; then
            echo 'CHINESE HYMN DETECTED: GENERATING THE SIMPLIFIED VERSION...'
            echo 'GENERATING THE PIANO VERSION...'
            if $ROOTDIR/resources/scripts/lilypondgen.sh p $hymntype $realnum true $debug $saveFile $force; then
                continue
            fi
            echo 'GENERATING THE GUITAR VERSION...'
            $ROOTDIR/resources/scripts/lilypondgen.sh g $hymntype $realnum true $debug $saveFile $force
            echo 'GENERATING THE GUITAR TEXT VERSION...'
            $ROOTDIR/resources/scripts/lilypondgen.sh gt $hymntype $realnum true $debug $saveFile $force
        fi
    fi

    count=$((count+1))
done

if [ $count -gt 0 ]; then
    echo "--------------------------------------------------------------------------------"
    echo "NOTICE: The lead sheets for $count song(s) have been generated."
    echo "--------------------------------------------------------------------------------"
fi
