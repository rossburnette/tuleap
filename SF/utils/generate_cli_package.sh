#!/bin/sh
#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX, 2001-2004. All Rights Reserved
# This file is licensed under the CodeX Component Software License
# http://codex.xerox.com
#
# $Id$
#
# Purpose:
#    Automatically re-generate online documentation
#

progname=$0
if [ -z "$scriptdir" ]; then 
    scriptdir=`dirname $progname`
fi

FORCE=0
HELP=0
VERBOSE=0
EXIST_CHANGE=0

# Check arguments
while	((1))	# look for options
do	case	"$1" in
	\-v*)	VERBOSE=1;;
	\-f*)	FORCE=1;;
	\-h*)	HELP=1;;
    \-d*)	DESTDIR=$2; shift;;
	*)	if [ ! -z "$1" ];
	    then
	        echo "Invalid option $1";
	        echo "Use -h flag to see all the valid options";
	        exit 2;
	    fi
	    break;;
	esac
	shift # next argument
done


if [ $HELP == 1 ]
then
    echo "Usage: generate_cli_package.sh [-f] [-v] [-h]";
    echo "  -f : force to generate the package without checking file dates";
    echo "  -v : verbose";
    echo "  -d : target directory where the archive will be stored";
    echo "  -h : help";
    exit 2;
fi

if [ -z "$DESTDIR" ]; then 
    echo "Please set the target repertory with the -d option";
    echo "Use -h flag to see all the valid options";
    exit 2;
fi

CURRENTDIR=`pwd`
# honor BASEDOCDIR if defined
if [ -z "$BASEDOCDIR" ]; then 
    BASEDOCDIR=/home/httpd/documentation
fi
CMDDOCDIR=$BASEDOCDIR/cli/cmd

# honor BASESRCDIR if defined
if [ -z "$BASESRCDIR" ]; then 
    BASESRCDIR=/home/httpd/cli
fi

# Check if the package exists. If not, we force the generation
mkdir -p $DESTDIR
cd $DESTDIR
if [ ! -e $DESTDIR/CodeX_CLI.zip ]; then
    FORCE=1;
fi

if [ $FORCE != 1 ]
then
    # check if need some update with CLI source code (and nusoap symbolic link too)
    COUNT=`find $BASESRCDIR -newer $DESTDIR/CodeX_CLI.zip | wc -l`
    if [ $COUNT == 0 ]
    then
        # No changes in the CLI source code
        if [ $VERBOSE == 1 ]
        then
            echo "No changes in the CLI source code";
        fi
    else 
        EXIST_CHANGE=1;
        if [ $VERBOSE == 1 ]
        then
            echo "Changes found in the CLI source code";
        fi
    fi
fi

if [ $FORCE != 1 ]
then
    # check if need some update with CLI documentation
    COUNT=`find $BASEDOCDIR/cli/xml -newer $DESTDIR/CodeX_CLI.zip | wc -l`
    if [ $COUNT == 0 ]
    then
        # No changes in the CLI documentation
        if [ $VERBOSE == 1 ]
        then
            echo "No changes in the CLI documentation";
        fi
    else 
        if [ $VERBOSE == 1 ]
        then
            echo "Changes found in the documentation";
            echo "Generating documentation";
            $scriptdir/generate_cli_doc.sh -v -f
        else
            $scriptdir/generate_cli_doc.sh -f
        fi
        EXIST_CHANGE=1;
    fi
else
    # force the documentation generation
    if [ $VERBOSE == 1 ]
    then
        echo "Generating documentation";
        $scriptdir/generate_cli_doc.sh -v -f
    else
        $scriptdir/generate_cli_doc.sh -f
    fi
fi

# Check here there is no change and if we don't force, then we exit
if [ $EXIST_CHANGE != 1 ]
then
    if [ $FORCE != 1 ]
    then
        # No changes in the archive
        if [ $VERBOSE == 1 ]
        then
            echo "No changes found in the files that compose the archive. Zip generation not necessary. Use -f to enforce the generation."
        fi
        exit 0
    fi
fi

# Use the tar command to make a complex copy :
# we copy the file contained in cli, documentation/cli/pdf, documentation/cli/html into /var/tmp,
# excluding the files .svn (subversion admin files) and *_old (old pdf documentation)
(cd /home/mnazaria/CodeX/dev_server/httpd; tar --exclude '.svn' --exclude "*_old.*" -h -cf - cli/ documentation/cli/pdf documentation/cli/html) | (cd /var/tmp; tar xf -)
cd /var/tmp
# We reorganize the files to fit the archive organization we want
mv documentation/cli cli/documentation
# We remove documentation (empty now)
rmdir documentation
# Rename the dir cli before creating the archive
mv cli CodeX_CLI

# zip the CLI package
if [ $VERBOSE == 1 ]
then
    /usr/bin/zip -r CodeX_CLI_new.zip CodeX_CLI
else
    /usr/bin/zip -q -r CodeX_CLI_new.zip CodeX_CLI
fi

# Then permute the new archive with the former one
if [ -f "$DESTDIR/CodeX_CLI.zip" ]; then
    cp -f $DESTDIR/CodeX_CLI.zip $DESTDIR/CodeX_CLI_old.zip > /dev/null
fi
mv CodeX_CLI_new.zip $DESTDIR/CodeX_CLI.zip

if [ $? != 0 ]
then
    cd "$CURRENTDIR"
    echo "CodeX CLI package generation failed!";
    exit 1
fi

# Then delete the copied files needed to create the archive
rm -r CodeX_CLI/*
rmdir CodeX_CLI

cd "$CURRENTDIR"
exit 0
