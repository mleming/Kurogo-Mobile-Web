<?php

includePackage('Social');

class SocialAPIModule extends APIModule
{
    protected $id = 'social';
    protected $vmin = 1;
    protected $vmax = 1;
    protected $feeds = array();
    protected $items = array();

    public function arrayForPost(SocialMediaPost $post, $data=null) {

        $array = array(
            'body'    =>$post->getBody(),
            'created' =>$post->getCreated()->format('U'),
            'sort' =>$post->getCreated()->format('U'),
            'author'  =>$post->getAuthor(),
        );
        
        if (isset($data['feed'])) {
            if ($author = $data['feed']->getUser($post->getAuthor())) {
                $array['author'] = $author->getName();
            }
        }
        
        return $array;
        
    }    
    protected function initializeForCommand() {

        $feeds = $this->loadFeedData();

        foreach ($feeds as $feed=>$feedData) {
            $modelClass = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : 'SocialDataModel';
            $this->feeds[$feed] = SocialDataModel::factory($modelClass, $feedData);
        }
        
        switch ($this->command)
        {
            case 'feeds':
                $feeds = array();
                foreach($this->feeds as $id => $controller) {
                    $feed = array(
                        'id'    => $id,
                        'title' => $controller->getTitle(),
                        'service'=> $controller->getServiceName(),
                        'account'=> $controller->getAccount(),
                        'serviceIcon'=>'', //@TODO,
                        'accountIcon'=>'' //@TODO
                    );
                    $feeds[] = $feed;
                }

                $this->setResponse($feeds);
                $this->setResponseVersion(1);

                break;
            case 'posts':
                $posts = array();
                
                if ($feed = $this->getArg('feed', null)) {
                
                    if (isset($this->feeds[$feed])) {
                        $feeds = array($this->feeds[$feed]);
                    } else {
                        KurogoDebug::debug($this->feeds, true);
                        throw new KurogoDataException("Invalid feed $feed");
                    }
                } else {
                    $feeds = $this->feeds;
                }
                
                foreach ($feeds as $feed=>$controller) {                                
                    if ($controller->canRetrieve()) {
                        $items = $controller->getPosts();
                        foreach ($items as $post) {
                            $item = $this->arrayForPost($post, array('feed'=>$controller));
                            $sort[] = $item['sort'];
                            $posts[] = $item;
                        }
                    } else {
                        throw new KurogoException("Authenticated feeds are not yet supported in the API");
                    }
                    
                }

                // @TODO sort by whatever 
                array_multisort($sort, SORT_DESC, $posts);
                
                $this->setResponse($posts);
                $this->setResponseVersion(1);
                
                break;
            case 'refresh':

                $items = array();
                $needToAuth = array();
                $posts = array();
                $sort = array();
                
                foreach ($this->feeds as $feed=>$model) {
                    if ($model->canRetrieve())
                    {
                        $model->setCacheLifetime(1);
                        $items = $model->getPosts();
                        foreach($items as $item)
                        {
                            file_get_contents($model->getUser($item->getAuthor())->getImageURL());
                        }
                    } else {
                        // Can't auth in an api.
                    }
                }
                $this->setResponse(true);
                $this->setResponseVersion(1);
            default:
                $this->invalidCommand();
                break;
                
        }
    }
}
