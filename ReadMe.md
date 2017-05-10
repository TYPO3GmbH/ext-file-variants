# translatable files

This extension serves as a working prototype for translatable assets.
The functionality will not interfere with any core concepts, but is target
to be included into the TYPO3 core.

## Features

- Upload language variants using the Filelist Module to provide variants transparently throughout the system.
- Replace or remove variants (the latter resets to the original file).
- Metadata records are can be translated, but will no longer all point to the same file record
- File Records are translatable now, but not directly accessible. All editing is done through metadata records.

## Limits

- Providing file variants is only possible in Filelist Module.
- Usage of sys_language_uid -1 (All languages) is deactivated.

## Setup

1. Install extension via composer or ExtensionManager
2. Activate extension
3. If activation does not happen via EM, make sure to create the folder in typo3temp
4. (optional) Use the extension configuration to create a dedicated file storage. If you use no dedicated storage,
a dedicated folder will be used in default storage.

### Data Examples

- Default language english, uid 0
- First language german, uid 1
- Second language spain, uid 2
- Third language russian, uid 3

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

## Behaviour

After Installation, the sys_file_metadata edit mask in Filelist Module is slightly changed. Nothing happens for the default language records. But creating / editing a tranlation record offers,
next to the fileinfo, a possiblity to upload a new file into the record. This file will recide in the dedicated translation storage or folder. After uploading, the fileinfo element changes its 
content and displays the uploaded file.
A button next to it allows for reset to the file used in default language. Also, the uploader is displayed again, so the file can be replaced at will. The formerly used one is lost.

During this process, all sys_file_reference entries are searched for a link to the default file, and replaced with the translated one.

Upon each translation action to any record, that features a FAL consuming field (like files or images), a check is performed to find out whether a file variant for the target language is available in the system. If it is, the resulting sys_file_reference record will link to that variant instead of the default language image.

This results in a consistent behaviour, that summarizes as:
- if a variant is available, it will be used. Everywhere and Everytime. Only exception of the rule: the table pages and pages_language_overlay are not file variants aware.
- if no variant is available, default file is used (current standard core behaviour).

## Missing Features

1. Upgrade Wizard. If metadata record translations are already there, no variants are provided nor used.
2. Summary of available variants for the default language metadata record.
3. Display of default language file for the translated metadata record (to know to what file the reset will lead).
4. Workspaces Support.