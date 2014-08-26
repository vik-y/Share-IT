<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Shareit extends CI_Controller
{

    function __construct()
    {
        parent::__construct();

    }

    /*
     * Outline of the entire application
     *
     * Features Needed ->
     *  Add Book
     *  Delete Book
     *  Edit Existing Book
     *
     * Keep Counter of Books ->
     *  Deal with Duplication
     *
     * Add a Book only after verification
     *  - Does the user need to be logged in before adding the book?
     *  or - let them add books and do email verification to confirm the book addition
     *  - You don't have to be logged in to add any book
     *  - Use the central database of the email IDs to  make this platform better
     *
     *
     */

    public function index(){

    }

    public function home(){

    }

    /* Form submission will call add_article method. Validation is checked in this method name. If validation successful, will pass data to the model and get the response and message back. Once response comes, will pass to the view in the form of JSON. */

    public function add_book()
    {
        //check if data has been sent through post or not
        if($this->input->post())
        {
            //start validation
            $this->load->library( array('form_validation'));
            $this->load->helper('form');

            $this->form_validation->set_rules('article_name', 'Article Name', 'trim|required|xss_clean|min_length[2]');
            $this->form_validation->set_rules('category_id', 'Category Id', 'trim|required|xss_clean|min_length[2]');
            $this->form_validation->set_rules('article_content', 'Content', 'trim|required|xss_clean|min_length[10]');


            if($this->form_validation->run() == false){
                $message = validation_errors();
                $data = array('message' => $message,'status'=>0);
            }
            else{
                $articleName = $this->input->post('article_name');
                $article_category_id = $this->input->post('category_id');
                $articleContent = $this->input->post('article_content');
                $articleURL =  $this->create_uri($articleName);

                $data = array(
                    "article_name"=>$articleName,
                    "article_url"=>$articleURL,
                    "article_category_id"=>$article_category_id,
                    "article_content"=>$articleContent
                );
                $this->load->model('article_model');
                //Array will be returned from the below function which will have status and message
                $response = $this->article_model->add_article($data);
                $data = array('message' => $response['message'],'status'=>$response['status']);
                //file_put_contents('newfile.dat',$data);
            }
        }
        else{
            //If opened this method without post method, this will be displayed.
            $message = "Article details are required";
            $data = array('message' => $message,'status'=>0);
        }

        $this->output->set_content_type('application/json');
        $json = $this->output->set_output(json_encode($data));
        //return to the view once reach here
        return $data;
    }



    public function upload_image(){
        $filename = md5($_FILES['file']['name']);
        $config['upload_path'] = './uploads/';
        $config['allowed_types'] = 'gif|jpg|png';
        $config['max_size']	= '100000';
        $config['max_width']  = '10240';
        $config['max_height']  = '7680';
        $config['file_name'] = $filename;

        $this->load->library('upload', $config);

        if ( ! $this->upload->do_upload('file'))
        {
            $data = array('error' => $this->upload->display_errors());
        }
        else
        {
            $data = array('filelink' => base_url().'uploads/'.$filename.'.png');
        }
        $this->output->set_content_type('application/json');
        $json = $this->output->set_output(json_encode($data));
        //return to the view once reach here
        return $data;

    }

    

    public function categoryview(){
        $breadcrumb = $this->breadcrumb->output();
        $this->breadcrumb->add('Articles', base_url().'articles');
        $this->breadcrumb->add('Category_Name_to bechanged', current_url());
        $breadcrumb = $this->breadcrumb->output();
        $data = array('breadcrumb'=> $breadcrumb);

        $this->load->view('view_help_header');
        $this->load->view('view_category_one', $data);
        $this->load->view('view_help_footer');
    }

    public function search_articles(){

        if($this->input->get()){
            $search_string = $this->input->get('string');
            $search_string = $this->security->xss_clean($search_string);
            $this->load->model('article_model');
            $data= $this->article_model->search_article($search_string);

        }
        else{
            $data = array('status'=>0, 'message'=>'Invalid Request');
        }
        $this->output->set_content_type('application/json');
        $this->output->set_output(json_encode($data));
        /*
         * Function for searching an article, returns all the articles which match.
         * Search the entire article
         */

    }


    public function search(){

        $breadcrumb = $this->breadcrumb->output();
        $this->breadcrumb->add('Articles', base_url().'articles');
        $this->breadcrumb->add('Search Result', current_url());
        $breadcrumb = $this->breadcrumb->output();
        $data['breadcrumb']= $breadcrumb;

        if($this->input->get() and strlen($this->input->get('string'))>=3){
            $search_string = $this->input->get('string');
            $search_string = $this->security->xss_clean($search_string);
            $data['search_string'] = $search_string;
            $this->load->view('view_help_header');
            $this->load->view('view_articles_search', $data);
            $this->load->view('view_help_footer');
        }
        else{
            echo "Error"; //Dispaly error page
        }
    }

    public function share_count(){
        require_once(APPPATH.'libraries/sharecount'.EXT);
        $obj = new shareCount('http://www.mashable.com');
        echo $obj->get_tweets().'<br>'; //to get tweets
        echo $obj->get_fb().'<br>'; //to get facebook total count (likes+shares+comments)
        echo $obj->get_linkedin().'<br>'; //to get linkedin shares
        echo $obj->get_plusones().'<br>'; //to get google plusones
        echo $obj->get_pinterest().'<br>'; //to get pinterest pins
    }
    /* Functions */

    /* Slug creation starts */

    /**
     * Create a uri string
     *
     * This wraps into the _check_uri method to take a character
     * string and convert into ascii characters.
     *
     * @param   mixed (string or array)
     * @param   int
     * @uses    Slug::_check_uri()
     * @uses    Slug::create_slug()
     * @return  string
     */
    public function create_uri($data = '', $id = '')
    {
        if (empty($data))
        {
            return FALSE;
        }

        if (is_array($data))
        {
            if ( ! empty($data[$this->field]))
            {
                return $this->_check_uri($this->create_slug($data[$this->field]), $id);
            }
            elseif ( ! empty($data[$this->title]))
            {
                return $this->_check_uri($this->create_slug($data[$this->title]), $id);
            }
        }
        elseif (is_string($data))
        {
            return $this->_check_uri($this->create_slug($data), $id);
        }

        return FALSE;
    }

    // ------------------------------------------------------------------------

    /**
     * Create Slug
     *
     * Returns a string with all spaces converted to underscores (by default), accented
     * characters converted to non-accented characters, and non word characters removed.
     *
     * @param   string $string the string you want to slug
     * @param   string $replacement will replace keys in map
     * @return  string
     */
    public function create_slug($string)
    {
        $this->load->helper(array('url', 'text', 'string'));
        $string = strtolower(url_title(convert_accented_characters($string), '_'));
        return reduce_multiples($string, $this->_get_replacement(), TRUE);
    }

    // ------------------------------------------------------------------------

    /**
     * Check URI
     *
     * Checks other items for the same uri and if something else has it
     * change the name to "name-1".
     *
     * @param   string $uri
     * @param   int $id
     * @param   int $count
     * @return  string
     */
    private function _check_uri($uri, $id = FALSE, $count = 0)
    {
        $new_uri = ($count > 0) ? $uri.$this->_get_replacement().$count : $uri;
        $data['article_url'] = $new_uri;
        $this->load->model('article_model');
        $response = $this->article_model->check_slug_exists($data);
        if($response == "1"){
            return $this->_check_uri($uri, $id, ++$count);
        }
        else
        {
            return $new_uri;
        }

    }

    // ------------------------------------------------------------------------

    /**
     * Get the replacement type
     *
     * Either a dash or underscore generated off the term.
     *
     * @return string
     */


    private function _get_replacement()
    {
        return '_';
    }

    /* slug creation ends */

    /* Breadcrumbs */

}