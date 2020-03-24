  public function index() {
    $<!----table_name----> = new <!----class_name---->($this->dbh);
    $limit = 10 * (isset($this->request['page']) ? $this->request['page'] : 1);
    $offset = 10 * (isset($this->request['page']) ? $this->request['page'] - 1 : 0);

    $datas = $<!----table_name---->->where('<!----class_name---->.id', '>', 0)->limit($limit)->offset($offset)->find('all');

    $ref = isset($this->request['page']) ? $this->request['page'] : 0;
    $next = isset($this->request['page']) ? $this->request['page'] + 1 : 2;

    $this->set('Title', '<!----class_name----> List');
    $this->set('datas', $datas);
    $this->set('<!----class_name---->', $datas);
    $this->set('ref', $ref);
    $this->set('next', $next);
  }

  public function show() {
    $datas = null;
    $id = $this->request['id'];

    $<!----table_name----> = new <!----class_name---->($this->dbh);
    $datas = $<!----table_name---->->where('<!----class_name---->.id', '=', $id)->find('first');
    $this->set('Title', '<!----class_name----> Ditail');
    $this->set('<!----class_name---->', $datas['<!----class_name---->']);
    $this->set('datas', $datas);
  }

  public function create() {
    $this->debug->log("<!----class_name---->Controller::create()");
    $<!----table_name----> = new <!----class_name---->($this->dbh);
    $form = $<!----table_name---->->createForm();
    $this->set('Title', '<!----class_name----> Create');
    $this->set('<!----class_name---->', $form['<!----class_name---->']);
  }

  public function save(){
    $this->debug->log("<!----class_name---->Controller::save()");
    try {
      $this->dbh->beginTransaction();
      $<!----table_name----> = new <!----class_name---->($this->dbh);
      $<!----table_name---->->save($this->request);
      $this->dbh->commit();
      $url = BASE_URL . '<!----class_name---->' . '/show/' . $<!----table_name---->->primary_key_value . '/';
      $this->redirect($url);
    } catch (\Exception $e) {
      $this->debug->log("<!----class_name---->Controller::create() error:" . $e->getMessage());
      $this->set('Title', '<!----class_name----> Save Error');
      $this->set('error_message', '保存ができませんでした。');
    }
  }

  public function edit() {
    $this->debug->log("<!----class_name---->Controller::edit()");
    try {
      $datas = null;
      $id = $this->request['id'];

      $<!----table_name----> = new <!----class_name---->($this->dbh);
      $datas = $<!----table_name---->->where('<!----class_name---->.id', '=', $id)->find('first');
      $this->set('Title', '<!----class_name----> Edit');
      $this->set('<!----class_name---->', $datas['<!----class_name---->']);
      $this->set('datas', $datas);
    } catch (\Exception $e) {
      $this->debug->log("<!----class_name---->Controller::edit() error:" . $e->getMessage());
    }
  }

  public function delete() {
    try {
      $this->dbh->beginTransaction();
      $<!----table_name----> = new <!----class_name---->($this->dbh);
      $<!----table_name---->->delete($this->request['id']);
      $this->dbh->commit();
      $url = BASE_URL . '<!----class_name---->' . '/index/';
    } catch (\Exception $e) {
      $this->debug->log("UsersController::delete() error:" . $e->getMessage());
    }
  }


