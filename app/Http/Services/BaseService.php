<?php

namespace App\Http\Services;

use App\Traits\FileUploader;

abstract class BaseService
{
    use FileUploader;

    protected string $title;
    protected string $route;
    protected string $view;
    protected string $path;
    protected string $access;

    public function __construct()
    {
        $this->path = '';
        $this->view = '';
        $this->access = '';
        $this->title = '';
        $this->route = '';
    }

    abstract public function model();

    public function query()
    {
        $model = $this->model();
        return $model::query();
    }

    public function getListData($desc=true, $columnDesc='id')
    {
        $query = $this->query();
        if($desc){
            $query = $query->orderBy($columnDesc, 'desc');
        }

        return $query->get();
    }

    public function getById($id)
    {
        $query = $this->query();
        return $query->find($id);
    }

    public function setModuleData($title, $route, $path, $view, $access): void
    {
        $this->title = $title;
        $this->route = $route;
        $this->view = $view;
        $this->path = $path;
        $this->access = $access;
    }
}
