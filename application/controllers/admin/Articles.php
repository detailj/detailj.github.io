<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Articles extends Controller {
    public function index(){
        //加载分页类库
        $this->load->library('pagination');
        //获取分页类配置
        $config = $this->getPaginationConfig();
        $this->pagination->initialize($config);


        $row = $this->uri->segment(4);
        $row = isset($row) ? $row : 0;

        $this->load->model('articles_model');
        $data['data'] = $this->articles_model->getArticlesDuring($row, $config['per_page']);

        $this->load->model('category_model');
        $data['all_category'] =  $this->category_model->getAllCategory();

        $data['cur_title'] = array('','','active','','','','');
        $this->load->view('admin/header');
        $this->load->view('admin/menu', $data);
        $this->load->view('admin/articles_index', $data);
        $this->load->view('admin/footer');
    }

	public  function edit($id=0){

        $data['cur_title'] = array('','','active','','','','');

        $this->load->model('category_model');
        $data['all_category'] =  $this->category_model->getAllCategory();
        if($id != 0){
            $this->load->model('articles_model');    
            $data['article'] = $this->articles_model->getArticle($id);      
        }
        else
        {
            $id=0;
            $this->load->model('articles_model');    
            $data['article'] = $this->articles_model->getArticle($id); 
        }
        $this->load->view('admin/header');
        $this->load->view('admin/menu', $data);
        $this->load->view('admin/articles_edit', $data);
        $this->load->view('admin/footer');
        
	}
    public  function add(){

        $data['cur_title'] = array('','','active','','','','');

        $this->load->model('category_model');
        $data['all_category'] =  $this->category_model->getAllCategory();

        $this->load->view('admin/header');
        $this->load->view('admin/menu', $data);
        $this->load->view('admin/articles_add', $data);
        $this->load->view('admin/footer');
        
    }

    public  function update($id=0){
        $this->load->database();

        $data['cur_title'] = array('','active','','','');
        $data['data'] = array(
                'id' => $id,
                'title' => $_POST['title'],
                'keyword' => $_POST['keyword'],
                'description' => $_POST['description'],
                'imagelink' => $_POST['imagelink'],
                'content' => $_POST['content'],
                'published_at' => $_POST['published_at'],
                'category' => $_POST['category'],
                'tag' => $_POST['tag'],  
                'pv' => '1'             
            );
        //获取表中该文章相关的标签
        $this->load->model('tag_model');
        $tmp =  $this->tag_model->getTagById($data['data']['id']);
        $article_tag = array();
        foreach ($tmp as $key => $value) {
            array_push($article_tag,$value['tag_name']);
        }
        //输入标签内容
        $tag_arr = explode(',', $data['data']['tag']);
        //比较输入内容与原文章标签的不同
        $diff = array_merge(array_diff($article_tag, $tag_arr),array_diff($tag_arr, $article_tag));

        //获取所有标签信息
        $all_tags =  $this->tag_model->getAllTags();
        foreach ($all_tags as $key => $value) {
            $all_tags_name[] = $value['tag_name'];
        }

        //判断用户输入的标签是否存在，如果不存在，创建该标签，并随机选择一个颜色
        $random_color = array('primary','success','info','warning','danger');
        foreach ($tag_arr as $key => $value) {
            if(!in_array($value, $all_tags_name)){
                $color = array_rand($random_color);
                $sql = "INSERT INTO `tag`(`tag_name`, `tag_button_type`) VALUES ('$value',"."'$random_color[$color]'".")";
                $this->db->query($sql);
            }
        }

        if($data['data']['id'] != 0)
        {
            //将标签录入articles_tag关系表
            
            //判断标签与文章的关系，进行删除或者添加关系的操作
            foreach ($diff as $key => $value) {
                if (in_array($value, $article_tag)){
                    $delete_link = $this->db->query("select a.article_id,a.tag_id FROM `article_tag` as a join `articles` as b on a.article_id=b.id join `tag` as c on c.id=a.tag_id WHERE b.id ='{$data['data']['id']}' AND c.tag_name= '{$value}'")->result_array();
                    $this->db->query("delete FROM `article_tag` WHERE article_id ='{$delete_link['0']['article_id']}' AND tag_id= '{$delete_link['0']['tag_id']}'");
                }
                if(!in_array($value, $article_tag)){

                    $insert_link = $this->db->query("select b.id as article_id,c.id as tag_id FROM `articles` as b join `tag` as c WHERE b.id ='{$data['data']['id']}' AND c.tag_name= '{$value}'")->result_array();

                    $this->db->query("INSERT INTO `article_tag`(`article_id`, `tag_id`) VALUES ({$insert_link['0']['article_id']},{$insert_link['0']['tag_id']})");
                }
            }
            $this->db->where('id', $data['data']['id']);
            $this->db->replace('articles', $data['data']);
        }
        else
        {
            $this->db->insert('articles', $data['data']);

            foreach ($diff as $key => $value) {
                $insert_link = $this->db->query("select b.id as article_id,c.id as tag_id FROM `articles` as b join `tag` as c WHERE b.title ='{$data['data']['title']}' AND c.tag_name= '{$value}'")->result_array();
                echo "select b.id as article_id,c.id as tag_id FROM `articles` as b join `tag` as c WHERE b.id ='{$data['data']['id']}' AND c.tag_name= '{$value}'";
                $this->db->query("INSERT INTO `article_tag`(`article_id`, `tag_id`) VALUES ({$insert_link['0']['article_id']},{$insert_link['0']['tag_id']})");
            }
            
        }

        redirect('/admin/articles/index');
      
    }

    public  function delete($id){
        $this->load->database();

        $data['cur_title'] = array('','active','','','');

        $this->db->where('id', $id);
        $this->db->delete('articles');

        redirect('/admin/articles/index');

    }
    private function getPaginationConfig(){
        $this->load->database();
        $this->load->helper('url');

        $config['base_url'] = site_url('admin/Articles/index');
        $config['total_rows'] = $this->db->count_all('articles');
        $config['per_page'] = '5';
        $config['num_links'] = 3 ;
        $config['last_link'] = '末页';
        $config['first_link'] = '首页';
        $config['prev_link'] = false;
        $config['next_link'] = false;
        $config['first_tag_open'] = '<li>';
        $config['first_tag_close'] = '</li><li><a>...</a></li>';
        $config['last_tag_open'] = '<li><a>...</a></li><li>';
        $config['last_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="active"><a href="">';
        $config['cur_tag_close'] = '</li></a>';
        $config['num_tag_open'] = '<li>';
        $config['num_tag_close'] = '</li>';
        $config['prev_tag_open'] = '<li>';
        $config['prev_tag_close'] = '</li>';
        $config['next_tag_open'] = '<li>';
        $config['next_tag_close'] = '</li>';
        return $config;
    }    

}
