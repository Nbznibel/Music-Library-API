<?php

namespace App\Core;


class XApp
{
    //::::::::::::::::::::::::::::::::::::::::::::::::::::

    // Audit Config Golbal Behaviour
    public const CFG_SEARCH_MAX_ITEMS     = 5;
    public const CFG_LIST_MAX_ITEMS     = 50;


    // product state
    public const LC_PRODUCT_CREATED         = 1;
    public const LC_PRODUCT_VALIDATED       = 2;
    public const LC_PRODUCT_ACTIVATED       = 3;



    // Audit errors types
    public const ERR_RES_NOT_FOUND  = 'resource_not_found';
    public const ERR_DATA_NOT_VALID = 'data_not_valid';
    public const ERR_ADD_CHILD_EXISTS  = 'add_child_exists';
    public const ERR_REFUSED_OPERATION  = 'refused_operation';
    public const ERR_FAILED_TRANSACTION  = 'failed_transaction';
    public const ERR_UNIQUE_VIOLATION  = 'unique_violation';
    public const IMPORT_ERROR  = 'failed_import';
    public const RESSOURCE_USED  = 'resource_used';



    // Audit Actions
    public const _AUDIT_ENTITY_READ              = 1;
    public const _AUDIT_ENTITY_CREATE            = 2;
    public const _AUDIT_ENTITY_UPDATE            = 3;
    public const _AUDIT_ENTITY_SOFT_DELETE       = 4;
    public const _AUDIT_ENTITY_FORCE_DELETE      = 5;
    public const _AUDIT_ENTITY_RESTORE_DELETE    = 6;
    public const _AUDIT_ENTITY_LIST              = 7;

    public const AUDIT_ENTITY_READ              = 'R';
    public const AUDIT_ENTITY_CREATE            = 'C';
    public const AUDIT_ENTITY_UPDATE            = 'U';
    public const AUDIT_ENTITY_SOFT_DELETE       = 'D';
    public const AUDIT_ENTITY_FORCE_DELETE      = 'X';
    public const AUDIT_ENTITY_RESTORE_DELETE    = 'Z';
    public const AUDIT_ENTITY_LIST              = 'L';


    // Search Index States
    public const SEARCH_INDEX_ACTIVE            = 1;
    public const SEARCH_INDEX_SOFT_DELETE       = 55;
    public const SEARCH_INDEX_FORCE_DELETE      = 99;



    // Front Types 
    public const FRONT_WEB_MASTER               = 1;
    public const FRONT_APP_MASTER               = 2;
}//Class
