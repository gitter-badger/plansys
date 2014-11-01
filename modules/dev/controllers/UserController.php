<?php

class UserController extends Controller {

    public function actionNewRole() {
        $model = new DevRoleForm;

        if (isset($_POST["DevRoleForm"])) {
            $model->attributes = $_POST["DevRoleForm"];
            if ($model->save()) {
                $this->redirect(array("roles"));
            }
        }
        $this->renderForm("DevRoleForm", $model);
    }

    public function actionRole($id) {
        $model = $this->loadModel($id, "DevRoleForm");
        if (isset($_POST["DevRoleForm"])) {
            $model->attributes = $_POST["DevRoleForm"];
            if ($model->save()) {
                $this->redirect(array("roles"));
            }
        }
        $this->renderForm("users.role.DevRoleForm", $model);
    }

    public function actionRoles() {
        $this->renderForm("users.role.DevRoleIndex");
    }

    public function actionDelete($id) {
        $this->loadModel($id, "DevUserForm")->delete();
    }

    public function actionUpdate($id) {
        $model = $this->loadModel($id, "DevUserForm");

        if (isset($_POST["DevUserForm"])) {
            $userRoles = $model->userRoles;
            if (!isset($_POST['DevUserForm']['subscribed']))
                $_POST['DevUserForm']['subscribed'] = '';

            $model->attributes = $_POST["DevUserForm"];
            if ($model->save()) {

                Yii::app()->user->setFlash('info', 'User berhasil disimpan');
            }
        }
        $this->renderForm("users.user.DevUserForm", $model);
    }

    public function actionNew() {
        $model = new DevUserForm;

        if (isset($_POST["DevUserForm"])) {
            $model->attributes = $_POST["DevUserForm"];

            if (isset($_GET['u']) && isset($_GET['f'])) {
                $model->username = $_GET['u'];
                $model->fullname = $_GET['f'];
                $model->useLdap = true;
            }

            if ($model->save()) {
                $model->subscribed = "on";
                $this->redirect(array("index"));
            }
        }
        $this->renderForm("users.user.DevUserForm", $model);
    }

    public function actionLdapSearch($q = "*") {
        $result = Yii::app()->ldap->user()->searchRaw($q);

        echo json_encode($result);
    }

    public function actionLdap() {
        $this->renderForm("users.user.DevUserLdap", [
            'data' => Yii::app()->ldap->user()->searchRaw('*')
        ]);
    }

    public function actionIndex() {
        $this->renderForm("users.user.DevUserIndex", [
            'useLdap' => Yii::app()->user->useLdap
        ]);
    }

}
