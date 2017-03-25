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