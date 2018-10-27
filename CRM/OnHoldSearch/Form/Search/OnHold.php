<?php
use CRM_OnHoldSearch_ExtensionUtil as E;

/**
 * A custom contact search
 */
class CRM_OnHoldSearch_Form_Search_OnHold
  extends CRM_Contact_Form_Search_Custom_Base
  implements CRM_Contact_Form_Search_Interface {

  public $_onHoldTypes;

  function __construct(&$formValues) {
    $this->_onHoldTypes = array(
      0 => 'Any Type',
      1 => 'Bounce',
      2 => 'Opt Out',
    );

    parent::__construct($formValues);
  }

  /**
   * Prepare a set of search fields
   *
   * @param CRM_Core_Form $form modifiable
   * @return void
   */
  function buildForm(&$form) {
    CRM_Utils_System::setTitle(ts('Email On Hold Search'));

    $form->add('select',
      'on_hold_type',
      ts('On Hold Type'),
      $this->_onHoldTypes,
      TRUE
    );

    $form->add('text',
      'sort_name',
      ts('Contact Name')
    );

    $form->add('text',
      'email',
      ts('Email')
    );

    // Optionally define default search values
    $form->setDefaults(array(
      'on_hold_type' => 0,
    ));

    /**
     * if you are using the standard template, this array tells the template what elements
     * are part of the search criteria
     */
    $form->assign('elements', array('on_hold_type', 'sort_name', 'email'));
  }

  /**
   * Get a list of summary data points
   *
   * @return mixed; NULL or array with keys:
   *  - summary: string
   *  - total: numeric
   */
  function summary() {
    return NULL;
    // return array(
    //   'summary' => 'This is a summary',
    //   'total' => 50.0,
    // );
  }

  /**
   * Get a list of displayable columns
   *
   * @return array, keys are printable column headers and values are SQL column names
   */
  function &columns() {
    // return by reference
    $columns = array(
      ts('Contact Id') => 'contact_id',
      ts('Name') => 'sort_name',
      ts('Contact Type') => 'contact_type',
      ts('Email') => 'email',
      ts('On Hold Type') => 'on_hold',
      ts('Hold Date') => 'hold_date'
    );
    return $columns;
  }

  /**
   * Construct a full SQL query which returns one page worth of results
   *
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $includeContactIDs
   * @param bool $justIDs
   * @return string, sql
   */
  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    // delegate to $this->sql(), $this->select(), $this->from(), $this->where(), etc.
    return $this->sql($this->select(), $offset, $rowcount, $sort, $includeContactIDs, NULL);
  }

  /**
   * Construct a SQL SELECT clause
   *
   * @return string, sql fragment with SELECT arguments
   */
  function select() {
    return "
      contact_a.id as contact_id  ,
      contact_a.contact_type as contact_type,
      contact_a.sort_name as sort_name,
      email.email,
      email.on_hold,
      email.hold_date
    ";
  }

  /**
   * Construct a SQL FROM clause
   *
   * @return string, sql fragment with FROM and JOIN clauses
   */
  function from() {
    return "
      FROM civicrm_contact contact_a
      LEFT JOIN civicrm_email email
        ON email.contact_id = contact_a.id
    ";
  }

  /**
   * Construct a SQL WHERE clause
   *
   * @param bool $includeContactIDs
   * @return string, sql fragment with conditional expressions
   */
  function where($includeContactIDs = FALSE) {
    $params = array();
    $count = 1;
    $clause = array(
      'contact_a.is_deleted = 0'
    );

    $on_hold_type = CRM_Utils_Array::value('on_hold_type', $this->_formValues);
    if (!empty($on_hold_type)) {
      $params[$count] = array($on_hold_type, 'Positive');
      $clause[] = "email.on_hold = %{$count}";
      $count++;
    }
    else {
      $clause[] = "email.on_hold != 0";
    }

    $name = CRM_Utils_Array::value('sort_name', $this->_formValues);
    if (!empty($name)) {
      $dao = new CRM_Core_DAO();
      $name = $dao->escape($name);
      $clause[] = "contact_a.sort_name LIKE '%{$name}%'";
    }

    $email = CRM_Utils_Array::value('email', $this->_formValues);
    if (!empty($email)) {
      $dao = new CRM_Core_DAO();
      $email = $dao->escape($email);
      $clause[] = "email.email LIKE '%{$email}%'";
    }

    if (!empty($clause)) {
      $where = implode(' AND ', $clause);
    }

    return $this->whereClause($where, $params);
  }

  /**
   * Determine the Smarty template for the search screen
   *
   * @return string, template path (findable through Smarty template path)
   */
  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  /**
   * Modify the content of each row
   *
   * @param array $row modifiable SQL result row
   * @return void
   */
  function alterRow(&$row) {
    //Civi::log()->debug('', array('row' => $row, '$this->_onHoldTypes' => $this->_onHoldTypes));

    $row['on_hold'] = $this->_onHoldTypes[$row['on_hold']];
  }
}
