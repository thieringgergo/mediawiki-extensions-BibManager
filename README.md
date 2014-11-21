mediawiki-extensions-BibManager fork by thieringgergo
===============================
Demo page:
http://wiki.kfki.hu/nano/Main_Page

compared to 1.23 this version has
- Developed for the Legacy LTS 1:1.19.20+dfsg-0+deb7u1 mediawiki package from debian whezy. +mysql
- Bug free edit+delete bib entries (hopefully)
- [1], [2] etc. numbering
- Doi support, checks duplicates with doi. Genereates links with doi also!
- If there is no Doi supplied, uses the Url data tag. Last fallback option is the Google scholar search, from the title.
- limited extra charachter latex őúöüóáű support, replaces the unknown ones to their fallback charachters (ő-> to o for example) Unicode charachters are working with bibmanager, but most websites exports .bib with the LaTeX standards, thus without any unicode or UTF-8.
- sort option for bibprint with \<bibprint filter="author:%author%" sort="year" /\>
- More fancy style
