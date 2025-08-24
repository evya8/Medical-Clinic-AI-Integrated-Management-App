<?php

namespace MedicalClinic\Controllers;

class InventoryController extends BaseController
{
    public function getInventory(): void
    {
        $this->requireAuth();

        $inventory = $this->db->fetchAll(
            "SELECT * FROM inventory ORDER BY item_name"
        );

        $this->success($inventory, 'Inventory retrieved successfully');
    }

    public function getInventoryItem(int $id): void
    {
        $this->requireAuth();

        $item = $this->db->fetch(
            "SELECT * FROM inventory WHERE id = :id",
            ['id' => $id]
        );

        if (!$item) {
            $this->error('Inventory item not found', 404);
        }

        $this->success($item, 'Inventory item retrieved successfully');
    }

    public function createInventoryItem(): void
    {
        $this->requireRole(['admin', 'pharmacist']);
        
        // Implementation for creating inventory items
        $this->success(null, 'Inventory item creation endpoint - to be implemented');
    }

    public function updateInventoryItem(int $id): void
    {
        $this->requireRole(['admin', 'pharmacist']);
        
        // Implementation for updating inventory items
        $this->success(null, 'Inventory item update endpoint - to be implemented');
    }

    public function deleteInventoryItem(int $id): void
    {
        $this->requireRole(['admin']);
        
        // Implementation for deleting inventory items
        $this->success(null, 'Inventory item deletion endpoint - to be implemented');
    }
}
