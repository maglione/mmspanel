#!/bin/sh
# Script para gerar uma arvore PEAR

PACOTES="Auth Config File_Gettext-beta HTML_Menu HTML_QuickForm HTML_QuickForm_Renderer_Tableless-beta HTML_QuickForm_advmultiselect HTML_Template_Flexy I18Nv2-beta Net_LDAP-beta HTTP_Session-beta PHP_Compat Structures_DataGrid-beta Structures_DataGrid_DataSource_Array-beta Structures_DataGrid_Renderer_HTMLTable-beta Structures_DataGrid_Renderer_Pager-beta Translation2-beta Validate-beta PhpDocumentor"

# Destino tmp
TARGET=/tmp/pear
# Diretorio fake para os pacotes
CRAP=/tmp/crap

rm -rf ${TARGET}
rm -rf ${CRAP}

# Parametro do PEAR
PEAR_PARAM="-C ${TARGET}/pear.conf  -c ${TARGET}/user.pear.conf -D php_dir=${TARGET} "
PEAR_PARAM="${PEAR_PARAM} -D bin_dir=${CRAP}  -D doc_dir=${CRAP} -D test_dir=${CRAP}"
PEAR_PARAM="${PEAR_PARAM} -D data_dir=${TARGET}"

PEAR=$(which pear)

#Instala o pear minimo e atualiza pra ultima versao stable
${PEAR} ${PEAR_PARAM} install -o Archive_Tar Console_Getopt XML_RPC Structures_Graph
${PEAR} ${PEAR_PARAM} upgrade --force --onlyreqdeps --ignore-errors PEAR
# agora muda pra utilizar o pear q acabou de ser instalado
PEAR="${CRAP}/pear ${PEAR_PARAM}"

# Agora instala os pacotes adicionais
for i in $PACOTES; do
  ${PEAR} install --force --onlyreqdeps $i;
done
#echo ${PEAR}

#compacta a pasta pearball 
OLDDIR=$(pwd)
cd /tmp
rm -rf /tmp/pear/.channels
rm -rf /tmp/pear/.registry
rm -rf /tmp/pear/cache
rm -f /tmp/pear/pearcmd.php
rm -f /tmp/pear/peclcmd.php
rm -f /tmp/pear/user.pear.conf
rm -f /tmp/pear/.filemap
rm -f /tmp/pear/.depdb
rm -f /tmp/pear/.lock
rm -f /tmp/pear/.depdblock

DATE=`date +%Y.%m.%d`
ARQ=pearball-${DATE}.tgz

tar czf $ARQ pear/
cp $ARQ $OLDDIR/
rm -f $ARQ;
rm -f PEAR-1.3.6.tgz

cd $OLDDIR/
echo "Arquivo Criado: $ARQ"

