<?php

return [
    "yes" => "Yes",
    "no" => "No",
    "cancel" => "Cancel",
    "continue" => "Continue",
    "submit" => "Send",
    "save" => "Save",
    "unnamed" => "Nameless",
    "title" => [
        "create_user" => "Create a user",
        "edit_user" => "User %name%",
        "index_user" => "Users list",
        "profile" => "Edit my profile",
        "create_email" => "New email",
        "edit_email" => "Email « %name% »",
        "forward_email" => "Transfer from « %name% »",
        "index_email" => "List of emails",
        "search_index" => "Advanced search",
        "index_import" => "Importing data",
        "index_software" => "Software status",
        "edit_email_template" => "Template « %name% »",
        "create_email_template" => "New email template",
        "index_email_templates" => "List of email templates",
    ],

    "login" => [
        "fields" => [
            "email" => "Email",
            "password" => "Password",
            "login" => "Log in",
        ],

        "actions" => [
            "forgotPassword" => "Forgot your password?"
        ],

        "reset" => [
            "title" => "Reset your password",
            "subtitle" => "Forgot your password?",
            "explanation" => "Enter the email address associated with your account below to receive a password reset link.",
            "email:label" => "Email Address",
            "email:placeholder" => "john.doe@mail.com",
            "submit" => "Send the reset link",

            "message" => [
                "noEmail" => "You must enter the email address associated with your account.",
                "invalidEmail" => "The email address you entered is invalid.",
                "userNotFound" => "No account appears to match this email address.",
                "userInactivee" => "The account associated with this email address is banned or disabled.",
                "unknownError" => "An error has occurred, please try again later.",
                "emailSent" => "An email has been sent to reset your password.",
            ],

            "email" => [
                "subject" => "Reset your password",
                "content" =>
                    "A password reset request has been submitted on your behalf.\n\n" .
                    "To make the change, <a href='%reset_url%' target='_blank'>click here</a> or type the link below in the address bar of your browser:\n" .
                    "%reset_url%\n\n" .
                    "If you have not made this request, please ignore this email.",
            ],

            "change" => [
                "title" => "Change your password",
                "explanation" => "Enter your new password below.",
                "newPassword" => "New password",
                "newPassword:placeholder" => "Enter a new password",
                "newPasswordConfirmation" => "Confirmation",
                "newPasswordConfirmation:placeholder" => "Confirm your new password",
                "submit" => "Update",

                "message" => [
                    "invalidLink" => "This password reset link is invalid or has expired.",
                    "alreadyUsedLink" => "This password reset link has already been used.",
                    "passwordsDontMatch" => "Passwords do not match.",
                    "success" => "Your password has been successfully updated!",
                ],
            ]
        ],
    ],

    "privileges" => [
        "default_message" => "You do not have the required privileges to perform this action",
        "modules" => [
            "user" => "User",
            "software" => "Software",
            "import" => "Import"
        ],
        "labels" => [
    		"user" => [
    			"USER_EDIT_PRIVILEGES" => "Change a user's privileges",
    			"USER_CREATE_EDIT" => "Create / edit a user",
    			"USER_LIST" => "List users"
    		],
            "software" => [
                "SOFTWARE_UPDATE" => "Edit software data"
            ]
        ]
    ],

    "email" => [
        "actions" => [
            "addEmail" => "Add an email",
            "create" => "New email",
            "createTemplate" => "New template",
            "returnToList" => "Back to the list of emails",
            "forward" => "Transfer the selection",
            "deleteSelection" => "Delete selection",
            "forwardEmail" => "Forward",
            "forceSend" => "Force sending",
            "forceResend" => "Send again",
        ],

        "button" => [
            "save" => "Save",
            "send" => "Send",
            "forward" => "Forward",
        ],

        "breadcrumb" => [
            "index_email" => "List of emails",
            "index_email_templates" => "List of templates",
            "edit_email" => "%name%",
            "edit_email_template" => "Template « %name% »",
            "forward_email" => "%name%",
            "create_email" => "New email",
            "create_email_template" => "New template",
        ],

        "title" => [
            "create" => "New email",
            "createTemplate" => "New template",
            "edit" => "Email « %name% »",
            "editTemplate" => "Template « %name% »",
            "forward" => "Transfer of « %name% »",
            "list" => "List of emails",
            "templatesList" => "List of templates",
        ],

        "emptyList" => "No email in the list",

        "fields" => [
            "from" => "From",
            "to" => "To",
            "cc" => "CC",
            "bcc" => "BCC",
            "subject" => "Subjet",
            "draft" => "Draft",
            "text" => "Message",
            "html" => "Message",
            "attachment" => "Attachment",
            "module" => "Module",
            "preview" => "Preview",
            "user" => "User",
            "createdAt" => "Created the",
            "updatedAt" => "Modified",
        ],

        "logs" => [
            "actions" => "An action was taken on emails",
            "created" => "Email « %name% » was created",
            "updated" => "Email « %name% » has been changed",
            "forwarded" => "Email « %name% » has been forwarded",
        ],

        "messages" => [
            "success" => [
                "hasBeenCreated" => "The email « %name% » was successfully created",
                "hasBeenUpdated" => "The email « %name% » has been successfully modified",
                "hasBeenForwarded" => "The email « %name% » has been successfully transferred",
                "hasBeenForceSent" => "The email « %name% » was sent successfully",
                "actionForward" => "{1}The email « %subjects% » a été enregistré pour être transféré|]1,Inf]Emails « %subjects% » have been saved to be forwarded",
                "actionDelete" => "{1}The email « %subjects% » a été supprimé avec succès|]1,Inf]Emails « %subjects% » have been removed successfully",
            ],
            "warning" => [
                "actionDelete" => "The email « %subject% » could not be deleted because it is already sent",
            ]
        ],

        "placeholder" => [
            "contact" => "-- Select a contact --"
        ],

        "status" => [
            "sent" => "Sent",
            "draft" => "Draft",
            "unsent" => "Waiting to send",
            "unsent_error" => "Sending error"
        ],

        "tabs" => [
            "informations" => "Basic information",
        ],

        "javascript" => [
            "action" => [
                "message" => [
                    "forward" => "You are about to transfer the selected emails, they will be saved as « draft » so you can add one or more recipients. Do you want to continue ?",
                    "delete" => "You are about to delete the selected emails, do you want to continue?",
                ]
            ]
        ],
    ],

    "general" => [
        "actions" => [
            "activateSelection" => "Activate selection",
            "deactivateSelection" => "Disable selection",
            "deleteSelection" => "Delete selection",
            "archiveSelection" => "Archive selection",
        ],
        "buttons" => [
            "actions" => "Actions",
            "cancel" => "Cancel",
            "return" => "Return",
            "save" => "Save",
            "ok" => "Ok",
        ],
        "messages" => [
            "confirm" => [
                "archiveSelection" => "Are you sure you want to archive the selection?"
            ]
        ]
    ],

    "json" => [
        "actions" => [
            "createFile" => "Create a file",
        ],
        "fields" => [
            "file" => "File",
            "updatedAt" => "Last modification",
        ],
        "subtitle" => [
            "dataFile" => "Configurations and data for the application and various modules",
            "list" => "Configurations and data for the application and various modules",
        ],
        "title" => [
            "dataFile" => "Application data files",
            "list" => "Application data files",
        ],
        "readonly" => "Read only",
    ],

    "log" => [
        "emptyList" => "No event log",
        "fields" => [
            "date" => "Date",
            "message" => "Message",
            "user" => "User",
        ],
        "title" => [
            "list" => "List of event logs",
        ],
    ],

    "dashboard" => [
        "title" => [
            "base" => "Dashboard"
        ],

        "breadcrumb" => [
            "home" => "Dashboard"
        ]
    ],

    "user" => [
        "actions" => [
            "create" => "Create a user",
        ],

        "breadcrumb" => [
            "index_user" => "Users list",
            "edit_user" => "%name%",
            "create_user" => "New user"
        ],

        "emptyList" => "No users",

        "errors" => [
            "password" => [
                "mustBeIdentical" => "Password fields must be the same",
            ],
        ],

        "fields" => [
            "confirmPassword" => "Confirm password",
            "createdAt" => "Created the",
            "email" => "Email",
            "fullName" => "Full name",
            "homePhone" => "Call home",
            "mobilePhone" => "Mobile phone",
            "updatedAt" => "Modified",
            "function" => "Function",
            "department" => "Department",
            "password" => "Password",
            "privileges" => "Privileges",
            "status" => "Status",
            "isActive" => "Active member"
        ],

        "label" => [
            "definePrivileges" => "-- Set privileges --",
            "privilegesGroups" => "Privilege group",
        ],

        "logs" => [
            "actions" => "An action was performed on users",
            "created" => "The user « %name% » was created",
            "updated" => "The user « %name% » has been changed",
        ],

        "messages" => [
            "success" => [
                "actionActivate" => "{1}User « %names% » a été activé avec succès|]1,Inf]Users « %names% » have been successfully activated",
                "actionDelete" => "{1}User « %names% » a été supprimé avec succès|]1,Inf]Users « %names% » ont été supprimés avec succès",
                "actionDeactivate" => "{1}User « %names% » has been successfully disabled|]1,Inf]Users « %names% » have been successfully disabled",
                "hasBeenCreated" => "User « %name% » was successfully created",
                "hasBeenUpdated" => "User « %name% » has been successfully changed",
            ],
            "warning" => [
                "actionOwn" => "You can not do this action on your own user",
            ],
            "error" => [
                "emailAlreadyExists" => "The email « %email% » is associated with another user"
            ],
        ],

        "placeholders" => [
            "password" => "Leave empty if you do not want to change it",
        ],

        "status" => [
            "active" => "Active",
            "inactive" => "Inactive",
        ],

        # Values within this array will be available within global object application.user.lang
        "javascript" => [
            "action" => [
                "message" => [
                    "activate" => "You are about to activate selected users, would you like to continue?",
                    "deactivate" => "You are about to disable the selected users, do you want to continue?",
                    "delete" => "You are about to delete the selected users, do you want to continue?",

                ],
            ],
        ],
    ],

    "software" => [
        "update" => [
            "shellOutput" => "Message received by the deployment system",
            "warning" => [
                "codeChangedTitle" => "Some source files look different",
                "codeChangedMessage" => "It will not be possible to update the code using this interface as some code appears to have been changed directly without following the recommended deployment mode."
            ],
        ],
    ],

    "mastersearch" => [
        "fields" => [
            "advancedSearch" => "Enter your search",
            "result" => "Result of your search",
            "modules" => "Modules",
            "submit" => "Search"
        ],
        "modules" => [
            "quote" => "Submissions",
            "project" => "Projects",
            "organization" => "Organizations",
            "product" => "Products"
        ],
        "noResult" => "No result"
    ],

    "import" => [
        "fields" => [
            "file" => "Select file to import",
            "load_file" => "Upload file",
            "change_file" => "Change file",
            "import" => "Import",
            "worksheet" => "Select the sheet to import",
            "startingLine" => "Starting line",
            "columnField" => "Column %column%",
            "assignations" => "Assigning columns",
            "managementOptgroup" => "Management",
            "archiveOption" => "Archive",
        ],
        "title" => [
            "index_import" => "Import",
        ],
        "excel" => [
            "assignations" => [
                "select" => "Select..."
            ],
            "partialMissingRows" => "%rowCount% other rows that are not displayed will also be processed."
        ],
        "errors" => [
            "genericServerSide" => "A server error occurred during the import.",
            "privilege" => "You do not have the necessary privileges to perform this type of import.",
            "settings" => [
                "undefinedType" => "No import is defined for « %importType% ».",
                "undefinedEntity" => "The type of entity to import, « %entity% », is not defined.",
            ],
            "assignations" => [
                "requiredProperties" => "The following fields must be assigned in order to import: « %properties% »."
            ],
            "noData" => "No data was received to proceed with the import.",
            "noAssignations" => "You must assign the fields to the columns before importing.",
            "startingLineTooHigh" => "The starting line is too big compared to the number of lines in the imported file.",
            "typeError" => "A value set for %property% is invalid.",
        ],

        "success" => "The data has been imported successfully.",

        "preview" => [
            "modalTitle" => "Previewing the import",
            "success" => "Continuing, the import will result in the changes below.",
            "error" => "By continuing, the import may fail with the error message below.",
            "created" => "The following elements will be created:",
            "deleted" => "The following items will be deleted:",
            "archived" => "The following items will be archived:",
            "updated" => "The following items will be updated:",
            "noChanges" => "No changes will be made.",
            "submit" => "Proceed to import",
        ]
    ],

    "ajax" => [
        "processing" => "Treatment...",
        "excelToJson" => [
            "errors" => [
                "noFile" => "No files have been sent."
            ]
        ]
    ]
];
