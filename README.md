# File Variants

This extension serves as a working prototype for translatable files in TYPO3.

## Features

- Upload language variants using the File list module to provide variants transparently throughout the system.
- Replace or remove variants (which resets to the file of the default language).
- Metadata records are can be translated just like before, but no longer point to the same file record.
- File records are translatable, but not directly accessible. All editing is done through metadata records.

## Limits

- Providing file variants is only possible in File list module.
- Usage of `sys_language_uid=-1` (All languages) is deactivated.

## Setup

1. Install extension via Composer: `composer require t3g/file-variants`
2. Activate the extension
3. (optional) Use the extension configuration to create a dedicated file storage. If you use no dedicated storage,
a dedicated folder will be used in default storage.

### Data Examples

- Default language English, uid 0
- First language German (Deutsch), uid 1
- Second language Spain (Español), uid 2
- Third language Russian (Русский), uid 3

**sys_file**

| uid | sys_language_uid | l10n_parent | filename |
|-----|------------------|-------------|----------|
|  1  | 0                | 0           | en.pdf   |
|  2  | 1                | 1           | de.pdf   |
|  3  | 2                | 1           | en.pdf   |

(Notice that there is no Russian variant here.)

**sys_file_metadata**

| uid | sys_language_uid | l10n_parent | file | title   |
|-----|------------------|-------------|------|---------|
|  1  | 0                | 0           | 1    | English |
|  2  | 1                | 1           | 2    | Deutsch |
|  3  | 2                | 1           | 3    | Español |

**tt_content**

| uid | sys_language_uid | l10n_parent | media | title   |
|-----|------------------|-------------|-------|---------|
|  1  | 0                | 0           | 1     | English |
|  2  | 1                | 1           | 1     | Deutsch |
|  3  | 2                | 1           | 1     | Español |
|  4  | 3                | 1           | 1     | Русский |

**sys_file_reference**

local is sys_file, foreign is tt_content

| uid | sys_language_uid | l10n_parent | uid_local | uid_foreign |
|-----|------------------|-------------|-----------|-------------|
|  1  | 0                | 0           | 1         | 1           |
|  2  | 1                | 1           | 2         | 2           |
|  3  | 2                | 1           | 3         | 3           |
|  4  | 3                | 1           | 1         | 4           |

Schematically the following relations exist and are created/maintained automatically:

1. `tt_content:1` (English) -> `sys_file_reference:1` -> `sys_file:1` (English)
2. `tt_content:2` (German) -> `sys_file_reference:2` -> `sys_file:2` (German)
3. `tt_content:3` (Spanish) -> `sys_file_reference:3` -> `sys_file:3` (Spanish)
4. `tt_content:4` (Russian) -> `sys_file_reference:4` -> `sys_file:1` (English)

## Behaviour

After Installation, the file metadata (`sys_file_metadata`) edit mask in File list module is slightly changed. Nothing changes for the default language. But creating / editing a file metadata translation allows for uploading a new file for this translation. The upload works the same way as the File list module and can be found next to the file info. This file will reside in the dedicated translation storage or folder. After uploading, the fileinfo element changes its content and displays the uploaded file.

A button then allows for resetting to the file used in default language. **The file formerly used here is removed permanently!** Also, the upload control is displayed again, so the a new file can be uploaded at any time.

During this process, all file references (`sys_file_reference`) are searched for a link to the default file, and updated with the translated one.

On each translation action to any record that contains a FAL field (like files or images), a check is performed to find out whether a file variant for the target language is available. If it is, the resulting file reference will link to that file variant instead of the default file.

This results in a consistent behaviour, that summarizes as:

- If a variant is available for a specific language, it will be used, everywhere and everytime.
- If no variant is available for a specific language, the default file is used (current standard TYPO3 behaviour).

## Missing Features

1. Upgrade wizard: if file metadata translations already exist, no file variants are provided or added.
2. Workspaces support.
