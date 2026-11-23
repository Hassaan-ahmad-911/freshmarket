<?php

namespace App\Domain\Shared\Enums;

/**
 * Every permission name used in the app, in one place.
 *
 * Using an enum (instead of typing strings like 'manage products' all over
 * the code) means a typo becomes a PHP error instead of a silent security hole.
 */
enum Permission: string
{
    // Platform admin
    case AccessAdminPanel = 'access admin panel';
    case ManageSuppliers = 'manage suppliers';
    case ManageCategories = 'manage categories';
    case ManagePlatformSettings = 'manage platform settings';
    case ViewAllOrders = 'view all orders';
    case IssueRefunds = 'issue refunds';

    // Supplier (owner and/or staff)
    case ManageStore = 'manage store';
    case ManageProducts = 'manage products';
    case ManageSupplierOrders = 'manage supplier orders';
    case ViewEarnings = 'view earnings';
    case ManageStaff = 'manage staff';
    case ManagePayoutAccount = 'manage payout account';

    // Customer
    case PlaceOrders = 'place orders';
}
