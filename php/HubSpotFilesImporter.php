<?php
class HubSpotFilesImporter {
    private $hubspot;

    public function __construct($token) {
        $this->hubspot = \HubSpot\Factory::createWithAccessToken($token);
    }

    private function _getAllFolders() {
        $folders = [];

        $after = "";
        $isDataExists = false;

        try {
            do {
                $apiResponse = $this->hubspot->files()->foldersApi()->doSearch(NULL, $after);
        
                if ($apiResponse->getResults()) {
                    $isDataExists = true;
        
                    foreach ($apiResponse->getResults() as $folder) {
                        $createdAt = $folder->getCreatedAt();
                        $updatedAt = $folder->getUpdatedAt();
                        $folderId = $folder->getId();
                        $name = $folder->getName();
                        $parentFolderId = $folder->getParentFolderId() ?? NULL;
                        $path = $folder->getPath();
                        $archived = $folder->getArchived();
                        $nodeType = "folder";
        
                        $nodeId = 'fo' . $folderId;
        
                        $folders[$folderId] = [
                            'node_id' => $nodeId,
                            'folder_id' => $folderId,
                            'name' => $name,
                            'parent_folder_id' => $parentFolderId,
                            'path' => $path,
                            'archived' => $archived,
                            'node_type' => $nodeType,
                            'created_at' => $createdAt,
                            'updated_at' => $updatedAt
                        ];
                    }
        
                    $after = $apiResponse->getPaging() ? $apiResponse->getPaging()['next']['after'] : '';
        
                    if ($after == '') {
                        $isDataExists = false;
                    }
                }
            } while ($isDataExists);
        } catch (ApiException $e) {
            echo $e->getMessage();
            exit;
        }

        return $folders;
    }

    public function getAllFolders() {
        $folders = [];

        $folders = $this->_getAllFolders();

        $folders = array_values($folders);

        return $folders;
    }

    private function _getAllFiles() {
        $files = [];

        $after = "";
        $isDataExists = false;

        try {
            do {
                $apiResponse = $this->hubspot->files()->filesApi()->doSearch(NULL, $after);
        
                if ($apiResponse->getResults()) {
                    $isDataExists = true;
        
                    foreach ($apiResponse->getResults() as $file) {
                        $createdAt = $file->getCreatedAt();
                        $updatedAt = $file->getUpdatedAt();
                        $fileId = $file->getId();
                        $name = $file->getName();
                        $parentFolderId = $file->getParentFolderId() ?? NULL;
                        $path = $file->getPath();
                        $archived = $file->getArchived();
                        $size = $file->getSize();
                        $type = $file->getType();
                        $extension = $file->getExtension();
                        $defaultHostingUrl = $file->getDefaultHostingUrl();
                        $url = $file->getUrl();
                        $nodeType = "file";
                        
                        $nodeId = 'fi' . $fileId;
        
                        $files[$fileId] = [
                            'node_id' => $nodeId,
                            'file_id' => $fileId,
                            'name' => $name,
                            'parent_folder_id' => $parentFolderId,
                            'path' => $path,
                            'archived' => $archived,
                            'size' => $size,
                            'type' => $type,
                            'extension' => $extension,
                            'default_hosting_url' => $defaultHostingUrl,
                            'url' => $url,
                            'node_type' => $nodeType,
                            'created_at' => $createdAt,
                            'updated_at' => $updatedAt
                        ];
                    }
        
                    $after = $apiResponse->getPaging() ? $apiResponse->getPaging()['next']['after'] : '';
        
                    if ($after == '') {
                        $isDataExists = false;
                    }
                }
            } while ($isDataExists);
        } catch (ApiException $e) {
            echo $e->getMessage();
            exit;
        }

        return $files;
    }

    public function getAllFiles() {
        $files = [];

        $files = $this->_getAllFiles();

        $files = array_values($files);

        return $files;
    }

    public function getAllNodes() {
        $folders = [];

        $folders = $this->_getAllFolders();

        foreach ($folders as &$folder) {
            $parentNodeId = $folder['parent_folder_id'] ? $folders[$folder['parent_folder_id']]['node_id'] : NULL;
        
            $folder['parent_node_id'] = $parentNodeId;
        }
        unset($folder);

        $files = [];

        $files = $this->_getAllFiles();

        foreach ($files as &$file) {
            $parentNodeId = $folders[$file['parent_folder_id']]['node_id'];
        
            $file['parent_node_id'] = $parentNodeId;
        }
        unset($file);

        $folders = array_values($folders);
        $files = array_values($files);

        $nodes = array_merge($folders, $files);

        function sortNodes($a, $b) {
            return $a['name'] <=> $b['name'];
        }

        usort($nodes, 'sortNodes');

        return $nodes;
    }

    public function getTreeNodes() {
        return $this->_createTree($this->getAllNodes());
    }

    private function _createTree($nodes, $parentId = NULL) {
        $tree = [];
    
        foreach ($nodes as $node) {
            if ($node['parent_node_id'] == $parentId) {
                $tree[] = array_merge($node, ['children' => $this->_createTree($nodes, $node['node_id'])]);
            }
        }
    
        return $tree;
    }

    public function getBreadcrumbs($nodes, $nodeId) {
        $breadcrumbs = [];
    
        foreach ($nodes as $node) {
            if ($node['node_id'] == $nodeId) {
                $breadcrumbs[] = [
                    'node_id' => $node['node_id'],
                    'name' => $node['name'],
                    'node_type' => $node['node_type']
                ];
    
                if ($node['parent_node_id']) {
                    $breadcrumbs = array_merge($this->getBreadcrumbs($nodes, $node['parent_node_id']), $breadcrumbs);
                }
            }
        }
    
        return $breadcrumbs;
    }

    public function createDiskStructureFromTree($tree, $rootPath) {
        $this->_createDiskStructureFromTree($tree, $rootPath);
    }

    private function _createDiskStructureFromTree($tree, $rootPath) {
        foreach ($tree as $node) {
            $nodePath = $rootPath . '/' . $node['name'];

            if ($node['node_type'] == 'folder') {
                if (!file_exists($nodePath)) {
                    mkdir($nodePath);
                }

                $this->_createDiskStructureFromTree($node['children'], $nodePath);
            } else {
                $fileContent = file_get_contents($node['url']);

                if ($fileContent === false) {
                    die("Failed to fetch file from URL.");
                }

                $fileName = $nodePath . '.' . $node['extension'];

                if (file_put_contents($fileName, $fileContent) === false) {
                    die("Failed to save the file.");
                }
            }
        }
    }
}