#!/bin/bash

SCRIPT_HOME=`dirname $0`

NAME="sureinvoice"
RELEASE="1.0"
REPO_URL="https://svn2.uversainc.com/svn/sureinvoice/sureinvoice/trunk"
SVN_REV="HEAD"
TAG_URL="https://svn2.uversainc.com/svn/sureinvoice/sureinvoice/tags/$RELEASE"
BUILD_BASE="/tmp"
CELINI_APP="false"
CELINI_REV="HEAD"
INSTALLER_APP="true"
INSTALLER_REV="HEAD"
INSTALLER_CONFIG="`dirname $0`/installer/config.php"
INSTALLER_VERSIONS="`dirname $0`/installer/versions.php"
INSTALLER_VC="`dirname $0`/installer/SureInvoiceDetection.php"

# No need to mess with anything below here
BUILD_DIR="$BUILD_BASE/$NAME-$RELEASE"
if [ "HEAD" == $SVN_REV ]; then
	CURRENT_SVN_REV=`svn log $REPO_URL 2>/dev/null | head -2 | grep \| | cut -d\| -f1| cut -dr -f2`
else
	CURRENT_SVN_REV=$SVN_REV
fi

if [ -e $BUILD_DIR ]; then
	echo "Build dir ($BUILD_DIR) must not exist!"
	exit 2
fi

function clean_release() {
	NOPKG_FILE=$1
	BASE_DIR=$2
	if [ -e $NOPKG_FILE ]; then
		echo "Removing files listed in $NOPKG_FILE"
		while read LINE; do
			CLEAN_LINE=`echo $LINE | cut -d# -f1`
			if [ ! -z $CLEAN_LINE ]; then
				if [ ! -z $BASE_DIR ]; then
					echo "Removing file $BUILD_DIR/$BASE_DIR/$CLEAN_LINE from build." 
					rm -rf $BUILD_DIR/$BASE_DIR/$CLEAN_LINE
				else
					echo "Removing file $BUILD_DIR/$CLEAN_LINE from build." 
					rm -rf $BUILD_DIR/$CLEAN_LINE
				fi
			fi
		done < $NOPKG_FILE
	fi	
}

echo "Checking for existing tag in SVN"
svn ls $TAG_URL >/dev/null 2>&1
if [ $? -eq 0 ]; then
	echo "SVN tag already exists at $TAG_URL"
	echo "Please remove before releasing this version again!"
	exit 1
fi

echo "Tagging version in SVN"
#svn copy -m "Tagged release $RELEASE of $NAME" -r $SVN_REV $REPO_URL $TAG_URL
if [ $? -ne 0 ]; then
	echo "Could not create tag, aborting!"
	exit 2
fi

echo "Building $NAME $RELEASE into $BUILD_DIR"
echo "Exporting repository $REPO_URL at revision $CURRENT_SVN_REV to $BUILD_DIR"
svn export -r $SVN_REV $REPO_URL $BUILD_DIR

clean_release "$SCRIPT_HOME/no_package"

# Setup Celini
if [ "true" == "$CELINI_APP" ]; then
	echo "Exporting Celini for application at rev $CELINI_REV to $BUILD_DIR/Celini"
	svn export -r $CELINI_REV https://svn2.uversainc.com/svn/Celini/trunk $BUILD_DIR/Celini
	clean_release "$BUILD_DIR/Celini/setup/no_package"
fi

#Setup installer
if [ "true" == "$INSTALLER_APP" ]; then
	echo "Exporting installer for application at rev $INSTALLER_REV to $BUILD_DIR/installer"
	svn export -r $INSTALLER_REV https://svn2.uversainc.com/svn/installer/installer/trunk $BUILD_DIR/installer
	clean_release "$BUILD_DIR/installer/no_package" "installer"
fi

touch $BUILD_DIR/includes/global_config.php
chmod a+w $BUILD_DIR/includes/global_config.php
cp $INSTALLER_CONFIG $BUILD_DIR/installer
cp $INSTALLER_VERSIONS $BUILD_DIR/installer
cp $INSTALLER_VC $BUILD_DIR/installer
chmod a+w $BUILD_DIR/installer/tmp

echo "Creating release file $BUILD_BASE/$NAME-$RELEASE.tgz"
CUR_DIR=`pwd`
cd $BUILD_BASE
tar -czf "$NAME-$RELEASE.tgz" "$NAME-$RELEASE"
cd $CUR_DIR

echo "Removing build directory $BUILD_DIR"
rm -rf $BUILD_DIR
