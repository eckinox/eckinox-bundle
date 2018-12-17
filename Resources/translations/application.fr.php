<?php

return [
    "title" => [
        "create_user" => "Créer un utilisateur",
        "edit_user" => "Utilisateur %name%",
        "index_user" => "Liste des utilisateurs",
        "profile" => "Modifier mon profil",
        "create_email" => "Nouveau courriel",
        "edit_email" => "Courriel « %name% »",
        "forward_email" => "Transfert de « %name% »",
        "index_email" => "Liste des courriels",
        "search_index" => "Recherche avancée",
        "index_software" => "Statut du logiciel",
        "edit_email_template" => "Gabarit « %name% »",
        "create_email_template" => "Nouveau gabarit de courriel",
        "index_email_templates" => "Liste des gabarits de courriel",
    ],

    "email" => [
        "actions" => [
            "addEmail" => "Ajouter un courriel",
            "create" => "Nouveau courriel",
            "createTemplate" => "Nouveau gabarit",
            "returnToList" => "Retour à la liste des courriels",
            "forward" => "Transférer la sélection",
            "deleteSelection" => "Supprimer la selection",
            "forwardEmail" => "Transférer",
            "forceSend" => "Forcer l'envoi",
            "forceResend" => "Envoyer de nouveau",
        ],

        "button" => [
            "save" => "Enregistrer",
            "send" => "Envoyer",
            "forward" => "Transferer",
        ],

        "breadcrumb" => [
            "index_email" => "Liste des courriels",
            "index_email_templates" => "Liste des gabarits",
            "edit_email" => "%name%",
            "edit_email_template" => "Gabarit « %name% »",
            "forward_email" => "%name%",
            "create_email" => "Nouveau courriel",
            "create_email_template" => "Nouveau gabarit",
        ],

        "title" => [
            "create" => "Nouveau courriel",
            "createTemplate" => "Nouveau gabarit",
            "edit" => "Courriel « %name% »",
            "editTemplate" => "Gabarit « %name% »",
            "forward" => "Transfert de « %name% »",
            "list" => "Liste des courriels",
            "templatesList" => "Liste des gabarits",
        ],

        "emptyList" => "Aucun courriel dans la liste",

        "fields" => [
            "from" => "De",
            "to" => "À",
            "cc" => "CC",
            "bcc" => "BCC",
            "subject" => "Sujet",
            "draft" => "Brouillon",
            "text" => "Message",
            "html" => "Message",
            "attachment" => "Pièce jointe",
            "module" => "Module",
            "preview" => "Prévisualisation",
            "user" => "Utilisateur",
            "createdAt" => "Créé le",
            "updatedAt" => "Modifié le",
        ],

        "logs" => [
            "actions" => "Une action a été effectuée sur des courriels",
            "created" => "Le courriel « %name% » a été créé",
            "updated" => "Le courriel « %name% » a été modifié",
            "forwarded" => "Le courriel « %name% » a été transféré",
        ],

        "messages" => [
            "success" => [
                "hasBeenCreated" => "Le courriel « %name% » a été créé avec succès",
                "hasBeenUpdated" => "Le courriel « %name% » a été modifié avec succès",
                "hasBeenForwarded" => "Le courriel « %name% » a été transféré avec succès",
                "hasBeenForceSent" => "Le courriel « %name% » a été envoyé de avec succès",
                "actionForward" => "{1}Le courriel « %subjects% » a été enregistré pour être transféré|]1,Inf]Les courriels « %subjects% » ont été enregistrés pour être transférés",
                "actionDelete" => "{1}Le courriel « %subjects% » a été supprimé avec succès|]1,Inf]Les courriels « %subjects% » ont été supprimés avec succès",
            ],
            "warning" => [
                "actionDelete" => "Le courriel « %subject% » n'a pas pu être supprimé car il est déja envoyé",
            ]
        ],

        "placeholder" => [
            "contact" => "-- Sélectionner un contact --"
        ],

        "status" => [
            "sent" => "Envoyé",
            "draft" => "Brouillon",
            "unsent" => "En attente d'envoi",
            "unsent_error" => "Erreur d'envoi"
        ],

        "tabs" => [
            "informations" => "Informations de base",
        ],

        "javascript" => [
            "action" => [
                "message" => [
                    "forward" => "Vous êtes sur le point de transférer les courriels sélectionnés, ils seront enregistrés comme « brouillon » pour que vous puissiez ajouter un ou des destinataires. Voulez-vous continuer ?",
                    "delete" => "Vous êtes sur le point de supprimer les courriels sélectionnés, voulez-vous continuer ?",
                ]
            ]
        ],
    ],

    "general" => [
        "actions" => [
            "activateSelection" => "Activer la sélection",
            "deactivateSelection" => "Désactiver la sélection",
            "deleteSelection" => "Supprimer la sélection",
        ],
        "buttons" => [
            "actions" => "Actions",
            "cancel" => "Annuler",
            "return" => "Retour",
            "save" => "Enregistrer",
            "ok" => "Ok",
        ],
    ],

    "json" => [
        "actions" => [
            "createFile" => "Créer un fichier",
        ],
        "fields" => [
            "file" => "Fichier",
            "updatedAt" => "Dernière modification",
        ],
        "subtitle" => [
            "dataFile" => "Configurations et données relatifs à l'application et aux divers modules",
            "list" => "Configurations et données relatifs à l'application et aux divers modules",
        ],
        "title" => [
            "dataFile" => "Fichiers de données de l'application",
            "list" => "Fichiers de données de l'application",
        ],
        "readonly" => "Lecture seule",
    ],

    "log" => [
        "emptyList" => "Aucun journal d'événement",
        "fields" => [
            "date" => "Date",
            "message" => "Message",
            "user" => "Utilisateur",
        ],
        "title" => [
            "list" => "Liste des journaux d'événements",
        ],
    ],

    "privileges" => [
        "default_message" => "Vous n'avez pas les privilèges requis pour effectuer cette action",
    ],

    "user" => [
        "actions" => [
            "createUser" => "Créer un utilisateur",
        ],

        "breadcrumb" => [
            "index_user" => "Liste des utilisateurs",
            "edit_user" => "%name%",
            "create_user" => "Nouvel utilisateur"
        ],

        "emptyList" => "Aucun utilisateur",

        "errors" => [
            "password" => [
                "mustBeIdentical" => "Les champs de mot de passe doivent être identique",
            ],
        ],

        "fields" => [
            "confirmPassword" => "Confirmer le mot de passe",
            "createdAt" => "Créé le",
            "email" => "Courriel",
            "fullName" => "Nom complet",
            "homePhone" => "Téléphone à la maison",
            "mobilePhone" => "Téléphone mobile",
            "updatedAt" => "Modifié le",
            "function" => "Fonction",
            "department" => "Département",
            "password" => "Mot de passe",
            "privileges" => "Privilèges",
            "status" => "Statut",
            "isActive" => "Membre actif"
        ],

        "label" => [
            "definePrivileges" => "-- Définir les privilèges --",
            "privilegesGroups" => "Groupe de privilège",
        ],

        "logs" => [
            "actions" => "Une action a été effectuée sur des utilisateurs",
            "created" => "L'utilisateur « %name% » a été créé",
            "updated" => "L'utilisateur « %name% » a été modifié",
        ],

        "messages" => [
            "success" => [
                "actionActivate" => "{1}L'utilisateur « %names% » a été activé avec succès|]1,Inf]Les utilisateurs « %names% » ont été activés avec succès",
                "actionDelete" => "{1}L'utilisateur « %names% » a été supprimé avec succès|]1,Inf]Les utilisateurs « %names% » ont été supprimés avec succès",
                "actionDeactivate" => "{1}L'utilisateur « %names% » a été désactivé avec succès|]1,Inf]Les utilisateurs « %names% » ont été désactivés avec succès",
                "hasBeenCreated" => "L'utilisateur « %name% » a été créé avec succès",
                "hasBeenUpdated" => "L'utilisateur « %name% » a été modifié avec succès",
            ],
            "warning" => [
                "actionOwn" => "Vous ne pouvez pas faire cette action sur votre propre utilisateur",
            ],
            "error" => [

            ],
        ],

        "placeholders" => [
            "password" => "Laisser vide si vous ne voulez pas le changer",
        ],

        "status" => [
            "active" => "Actif",
            "inactive" => "Inactif",
        ],

        # Values within this array will be available within global object application.user.lang
        "javascript" => [
            "action" => [
                "message" => [
                    "activate" => "Vous êtes sur le point d'activer les utilisateurs sélectionnés, voulez-vous continuer ?",
                    "deactivate" => "Vous êtes sur le point de désactiver les utilisateurs sélectionnés, voulez-vous continuer ?",
                    "delete" => "Vous êtes sur le point de supprimer les utilisateurs sélectionnés, voulez-vous continuer ?",

                ],
            ],
        ],
    ],

    "software" => [
        "update" => [
            "shellOutput" => "Message reçu par le système de déploiement",
            "warning" => [
                "codeChangedTitle" => "Certains fichiers sources semblent différents",
                "codeChangedMessage" => "Il ne sera pas possible de mettre le code à jour en utilisant cette interface puisque du code semble avoir été changé directement sans suivre le mode de déploiement recommandée."
            ],
        ],
    ],

    "mastersearch" => [
        "fields" => [
            "advancedSearch" => "Saisir votre recherche",
            "result" => "Résultat de votre recherche",
            "modules" => "Modules",
            "submit" => "Rechercher"
        ],
        "modules" => [
            "quote" => "Soumissions",
            "project" => "Projets",
            "organization" => "Organisations",
            "product" => "Produits"
        ],
        "noResult" => "Aucun résultat"
    ]
];
