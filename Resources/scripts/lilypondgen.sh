#!/bin/bash
ROOTDIR=/Users/eric/Work/hymnal.net
export gentype=$1
export hymntype=$2
export hymnnum=$3
export isSimplifiedChinese=$4
export debug=$5
export savelyfile=$6
export force=$7
if [ "$gentype" == "" ] || [ "$hymntype" == "" ] || [ "$hymnnum" == "" ]; then
	echo "Usage: lilypondgen.sh <instrument> <type> <num> [<isSimplifiedChinese>=true|false] [<debug>=true|false] [<savelyfile>=true|false] [<force>=true|false]"
	exit
fi
export logfile=$ROOTDIR/code/resources/scripts/results.log
touch $logfile
if [ "$hymntype" == "test" ]
then
	export genpdf=true
	export genpng=false
	export gensvg=false
	export genmidi=false
	export showhymnnumber=false
	cd $ROOTDIR/code/resources/scripts
	php converttolilypond.php
	file='test_new_lilypond.ly'
	psfile='test_new_lilypond.ps'
	oldpdffile='test_new_lilypond.pdf'
	newpdffile="test_new_$gentype.pdf"
else
	export genpdf=true
	export genpng=false
	export gensvg=true
	if [ "$gentype" == "gt" ]; then
		export genpng=false
		export gensvg=false
		export genmidi=false
	else
		export genmidi=true
	fi
	if [ "$hymntype" == "m" ] || [ "$hymntype" == "ns" ] || [ "$hymntype" == "lb" ]; then
		export showhymnnumber=false
	else
		export showhymnnumber=true
	fi
	suffix=`echo $hymnnum | sed -e 's/[0-9]*//'`
	strippedHymnNum=`echo $hymnnum | sed -e 's/[^0-9]*//'`
	cd $ROOTDIR/code/resources/scripts
	php converttolilypond.php
	case $hymntype in
		"c")
			filename=`echo $strippedHymnNum | awk '{ printf("child%04d", $0) }'`;;
		"h")
			filename=`echo $strippedHymnNum | awk '{ printf("e%04d", $0) }'`;;
		"ch")
			filename=`echo $strippedHymnNum | awk '{ printf("c%04d", $0) }'`;;
		"ts")
			filename=`echo $strippedHymnNum | awk '{ printf("ts%04d", $0) }'`;;
		"de")
			filename=`echo $strippedHymnNum | awk '{ printf("g%04d", $0) }'`;;
		"hr")
			filename=`echo $strippedHymnNum | awk '{ printf("r%04d", $0) }'`;;
		"lb")
			filename=`echo $strippedHymnNum | awk '{ printf("lb%02d", $0) }'`;;
		"ns")
			filename=`echo $strippedHymnNum | awk '{ printf("ns%04d", $0) }'`;;
		"nt")
			filename=`echo $strippedHymnNum | awk '{ printf("e%04d", $0) }'`;;
		"m")
			filename=$hymnnum;;
	esac
	if [ $suffix ] && [ "$hymntype" != 'm' ]; then
		filename="$filename$suffix"
	fi
	if [ "$hymntype" == "nt" ]; then
		filename="$filename"_new
	fi
	originalFilename=$filename
	if [ "$isSimplifiedChinese" == "true" ]; then
	    filename="$filename"_cn
	fi
	file="./$filename"_lilypond.ly
	psfile="$filename"_lilypond.ps
	oldpdffile="$filename"_lilypond.pdf
	oldpngfile="$filename"_lilypond.png
	oldsvgfile="$filename"_lilypond.svg
	oldmidifile="$originalFilename"_lilypond.midi
	oldmidifile2="$filename"_lilypond.midi
	newpdffile="$filename"_"$gentype".pdf
	newpngfile="$filename"_"$gentype".png
	newsvgfile="$filename"_"$gentype".svg
	newmidifile="$originalFilename"_tune.midi
fi
case $hymntype in
	"test")
		path="$ROOTDIR/assets/Hymns";;
	"c")
		path="$ROOTDIR/assets/Hymns/Children";;
	"h")
		path="$ROOTDIR/assets/Hymns/Hymnal";;
	"ch")
		path="$ROOTDIR/assets/Hymns/Chinese";;
	"ts")
		path="$ROOTDIR/assets/Hymns/ChineseTS";;
	"de")
		path="$ROOTDIR/assets/Hymns/German";;
	"hr")
		path="$ROOTDIR/assets/Hymns/Russian";;
	"lb")
		path="$ROOTDIR/assets/Hymns/LongBeach";;
	"ns")
		path="$ROOTDIR/assets/Hymns/NewSongs";;
	"nt")
		path="$ROOTDIR/assets/Hymns/NewTunes";;
	"m")
		path="$ROOTDIR/assets/Hymns/Miscellaneous";;
esac

if [ ! -f $file ]; then
    exit 0
fi

mv $file $path/pdfs
cd $path/pdfs
hasDiff=true
if [ "$gensvg" == "true" ]; then
    hasExistingFile=false
    if [ -f "$path/svg/$newsvgfile" ]; then
        hasExistingFile=true
        cp $path/svg/$newsvgfile "$path/svg/$newsvgfile".backup
    fi
    /usr/local/bin/lilypond -dno-point-and-click -dbackend=svg $file 2>&1 # | tee -a $logfile
    mv $oldsvgfile "$path/svg/$newsvgfile"
    if [ "$force" == "false" ]; then
		if [ "$hasExistingFile" == "true" ]; then
			if diff -q "$path/svg/$newsvgfile".backup "$path/svg/$newsvgfile" > /dev/null; then
				echo "--------------------------------------------------------------------------------"
				echo "NOTE: No change is detected."
				echo "--------------------------------------------------------------------------------"
				hasDiff=false
			fi
		fi
    fi
	if [ "$hasExistingFile" == "true" ]; then
		rm "$path/svg/$newsvgfile".backup
	fi
fi
if [ "$hasDiff" == "true" ]; then
    if [ "$genpdf" == "true" ]; then
        /usr/local/bin/lilypond -dno-point-and-click --pdf $file 2>&1 #| tee -a $logfile
        mv $oldpdffile "$path/pdfs/$newpdffile"
    fi
    if [ "$genpng" == "true" ]; then
        /usr/local/bin/lilypond -dno-point-and-click --png $file 2>&1 #| tee -a $logfile
        mv $oldpngfile "$path/images/$newpngfile"
    fi
    if [ "$genmidi" == "true" ]; then
        /usr/local/bin/lilypond -dno-point-and-click $file 2>&1 #| tee -a $logfile
        rm $path/pdfs/$oldpdffile;
        mv $oldmidifile "$path/midi/tunes/$newmidifile"
    fi
fi

if [ -f $oldmidifile ]; then
    rm $oldmidifile
fi

if [ -f $oldmidifile2 ]; then
    rm $oldmidifile2
fi

if [ -f $psfile ]; then
    rm $psfile
fi

if [ "$savelyfile" != "true" ]; then
	rm $file
fi

if [ "$hasDiff" == "true" ]; then
	exit 1
else
	if [ "$force" == "true" ]; then
    	exit 1
	else
	    exit 0
	fi
fi
