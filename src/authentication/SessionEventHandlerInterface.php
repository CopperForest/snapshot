<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace copperforest\snapshot\authentication;
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
interface SessionEventHandler{
    
    function onCreate();
    
    function onResume();
    
    function onAuthenticate();
}
