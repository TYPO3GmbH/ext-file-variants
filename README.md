File Variants
=============

This extension brings translatable files to TYPO3. It provides the possibility for the editor to relate 
files with same content, but different language meaning to each other and use them transparently throughout
the system.

Behavior changes in following areas:
- File Module
- File Picker / Wizards
- Frontend Rendering

Backend Changes
...............

Upload a file and its language variants
,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,

The only place to define language variants of a file is the file module in Backend.
Uploading files in CEs or other FAL related upload possibilities will lead to that file being treated as first class,
thus default language variant.

The workflow will be
1. Upload the original, default language file in file module, as usual. Prepare the metadata record while doing so.
2. Close the form to come back to the list view. Select the metadata translation button for the language you wish to
provide a new file, thus a language variant for.
3. Translate the metadata properties you wish, and for the file field you upload the new file. Close the dialog.
4. Translate another metadata record, which should use the default language file. Just translate the properties you wish,
don't touch the file field. Upon submitting the form, the sys_file record of the default language file will be transparently
translated. The metadata record will relate to that new file record, which in turn knows about its parent.
5. Do so for all the record you wish to translate. If no translation is provided, the content language fallback chain will
be in place as before.

An extra storage will be provided by the extension, that serves as destination for the language variants. A fallback to 
default storage is in place.

Data Example
,,,,,,,,,,,,

Default language english, uid 0
First language german, uid 1
Second language spain, uid 2
Third language russian, uid 3

**sys_file**

| uid | sys_language_uid | l10n_parent | filename |
|-----|------------------|-------------|----------|
|  1  |       0          |     0       | en.pdf   |
|  2  |       1          |     1       | de.pdf   |
|  3  |       2          |     1       | en.pdf   |


**sys_file_metadata**

| uid | sys_language_uid | l10n_parent | file | title |
|-----|------------------|-------------|------|-------|
|  1  |       0          |     0       |  1   | en    |
|  2  |       1          |     1       |  2   | de    |
|  3  |       2          |     1       |  3   | spain |

Usage in FAL consuming FormEngine fields (like tt_content, pages)
,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,

The language variant handling will be mostly transparent to the editor.

The workflow will be:
1. create a new record in default language. The file wizard will present only default language records. Choose those
you wish to use with your record.
2. translate the record. The FAL field will not be displayed (in translate/connected mode). In free/copy mode again only default
language file records can be selected.

In the background, upon saving the changes to the record, DataHandler will analyse which sys_language_uid the record has
and if it is a connected/translated record.
If it is not default, it will look up the available file records (the language variant) for the one you chose.
If there is one with the same sys_language_uid, it will relate to this record. Otherwise the default one will be kept.

Data Example
,,,,,,,,,,,,

The values from example above are used to continue the scenario.

**tt_content**

| uid | sys_language_uid | l10n_parent | media | title   |
|-----|------------------|-------------|-------|---------|
|  1  |       0          |     0       |  1    | en      |
|  2  |       1          |     1       |  1    | de      |
|  3  |       2          |     1       |  1    | spain   |
|  4  |       3          |     1       |  1    | russian |

**sys_file_reference**

local is sys_file, foreign is tt_content

| uid | sys_language_uid | l10n_parent | uid_local | uid_foreign |
|-----|------------------|-------------|-----------|-------------|
|  1  |       0          |     0       |    1      |      1      |
|  2  |       1          |     1       |    2      |      2      |
|  3  |       2          |     1       |    3      |      3      |
|  4  |       3          |     1       |    1      |      4      |

Frontend Changes
................

In Frontend, always the language variant file will be rendered, if one is detected.


ToDos:

- disable FormEngine upload for FAL files (in connected mode translation scenario) - see DataProvider
- Upgrade wizard for sys_file translatable, relation to sys_file_metadata 1:1 afterwards
- TCA sys_file->metadata check maxitems, size
- check why sys_file->metadata is always 0
- disable sys_language_uid = -1 in TCA
- in copy mode sys_file_reference must relate a default language file record. Variants are to be ignored.

- DataProvider
-- create DataProvider depending on TcaInline, (depends, and before) in inlineParentRecord, maybe even tcaDatabaseRecord group 
(core/Configuration/DefaultConfiguration 540)
-- detect table, detect FAL fields (foreign_table = sys_file_reference)
-- are we in translation? (sys_language_uid > 0 && l[10|18]n_parent == 0) <== copy mode!! -> TCAInline->compileChild
-- InlineControlContainer->render -> inlineRecordContainer
-- types[showItem] -> remove everything, only header will be displayed
-- detect whether copy or translate mode is on
-- based on that, define rendering, either default or passive

- DataHandler
-- let a hook detect connected translation mode, in this case rewrite the file reference in sys_file_reference to relate
to the language variant.
-- other hook: upon availability of a new variant, update all consuming fields in connected mode. If a variants gets deleted,
let the referencing CEs use default.

- Frontend Renderer
-- check it accepts being served with a non default language relation and must not overlay on its own.
==> seems to be fine, tested with shortcut CE and default language references. No overlay done, even if translations are
available.

- File Module
-- given a file record has language variants, how to handle a delete? The copies might be referenced in some fields.
-- cascading delete will not be sufficient here probably.
-- Figure out how to upload without creating sys_file_references. Existing file records are no options, we need to create own one.
Otherwise I would translate a file using another one by manipulating sys_language_uid. It would fail if the file was in use
elsewhere as a default language item.
-- Exclude languageVariant Storage / Folder from search

- Setup
-- provide non browseable filestorage (https://docs.typo3.org/typo3cms/FileAbstractionLayerReference/Administration/Storages/Index.html)

- History
-- sys_file_metadata keeps track about changing input fields, but it seems not about changing files. Check that.
-- File records must not be replaced, but rather soft deleted in order to provide rollback. Check that.
-- probably no ressurection of file records possible. sys_file accepts no soft delete, the driver immediately does an unlink to the file.
There is something about the recycler, maybe this can be used. Investigate.


Limits:

- no upload possibility in CEs or other FormEngine provided sources anymore. File upload only in file module.
- sys_language_uid = -1 is not supported. Remove from the system.
- file variants will only take effect in connected mode.
- pages receives no support for file variants. If you can come up with a valid use case, that might change.
