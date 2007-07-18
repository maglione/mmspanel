XGETTEXT=/usr/bin/xgettext
MSGMERGE=/usr/bin/msgmerge
MSGFMT=/usr/bin/msgfmt
LOCALES=en_US pt_BR

clean:
	rm -vf $(CURDIR)/tmp/compile_dir/*
	echo "messages    = $(CURDIR)/locale"  > $(CURDIR)/locale/domains.ini
	echo "body.html   = $(CURDIR)/locale" >> $(CURDIR)/locale/domains.ini
	echo "footer.html = $(CURDIR)/locale" >> $(CURDIR)/locale/domains.ini
	echo "header.html = $(CURDIR)/locale" >> $(CURDIR)/locale/domains.ini
	echo "auth.html   = $(CURDIR)/locale" >> $(CURDIR)/locale/domains.ini

gettext: clean
	rm -f $(CURDIR)/locale/pt_BR/LC_MESSAGES/messages.pot
	touch $(CURDIR)/locale/pt_BR/LC_MESSAGES/messages.pot
	find $(CURDIR)/ -type f -iname '*.php' ! -ipath '$(CURDIR)/libs/pear/*' -exec $(XGETTEXT) -n -j --language=PHP --keyword=_tr --copyright-holder='Maglione Informatica' --msgid-bugs-address='daniel@maglione.com.br' -o $(CURDIR)/locale/pt_BR/LC_MESSAGES/messages.pot {} \;
	$(MSGMERGE) -U $(CURDIR)/locale/pt_BR/LC_MESSAGES/messages.po $(CURDIR)/locale/pt_BR/LC_MESSAGES/messages.pot

doc:
	rm -rf $(CURDIR)/docs
#	$(CURDIR)/libs/pear/PhpDocumentor/phpdoc -o HTML:frames:default --title "Maglione NetPanel" --sourcecode on --directory $(CURDIR) -t $(CURDIR)/docs --ignore *
	$(CURDIR)/libs/pear/PhpDocumentor/phpdoc -o HTML:frames:default --title "Maglione NetPanel" --sourcecode on -f `find ./ -type f -iname '*.php' ! -ipath './libs/pear/*' | tr "\n" ","` -t $(CURDIR)/docs --ignore *

all:
	$(MSGFMT) -o $(CURDIR)/locale/pt_BR/LC_MESSAGES/messages.mo $(CURDIR)/locale/pt_BR/LC_MESSAGES/messages.po
