// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Encapsules the behavior for creating a Wavefront 3D model in Moodle.
 *
 * Manages the UI while operations are occuring, including rendering and manipulating the model.
 *
 * @module     mod_wavefront/model_renderer
 * @class      model_renderer
 * @package    mod_wavefront
 * @copyright  2022 Ian Wild
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.9
 */

import WebGL from 'mod_wavefront/WebGL';
import * as THREE from 'mod_wavefront/three';
import { MTLLoader } from 'mod_wavefront/MTLLoader';
import { OBJLoader } from 'mod_wavefront/OBJLoader';
import { OrbitControls } from 'mod_wavefront/OrbitControls';

let camera, controls, scene, renderer;

var wavefront_stage;
var stage_width, stage_height; 
var lighting, ambient, keyLight, fillLight, backLight;

var windowHalfX = window.innerWidth / 2;
var windowHalfY = window.innerHeight / 2;

const animate = () => {
	 requestAnimationFrame(animate);
     renderer.render(scene, camera);
	 controls.update();
}

export const init = (stage, obj_file, mtl_file, baseurl, width, height, cameraangle, camerafar, camerax, cameray, cameraz) => {
    
    var container = document.getElementById(stage);
        	        	
	if (!WebGL.isWebGLAvailable() ) {
		
	    const warning = WebGL.getWebGLErrorMessage();
		document.getElementById( 'container' ).appendChild( warning );
	    return true;
	}
			
	wavefront_stage = stage;
	stage_width = width;
	stage_height = height;
		
	// Scene
	scene = new THREE.Scene();
	
	// Camera
	var SCREEN_WIDTH = stage_width, SCREEN_HEIGHT = stage_height;
	var VIEW_ANGLE = Number(cameraangle), ASPECT = SCREEN_WIDTH / SCREEN_HEIGHT, NEAR = 0.1, FAR = Number(camerafar);
	camera = new THREE.PerspectiveCamera( VIEW_ANGLE, ASPECT, NEAR, FAR);
	scene.add(camera);
	camera.position.set(Number(camerax),Number(cameray),Number(cameraz));	
	
	ambient = new THREE.AmbientLight(0xffffff, 1.0);	
	scene.add(ambient);
	// Lighting
	keyLight = new THREE.DirectionalLight(new THREE.Color('hsl(30, 100%, 75%)'), 1.0);
	keyLight.position.set(-100, 0, 100);

	fillLight = new THREE.DirectionalLight(new THREE.Color('hsl(240, 100%, 75%)'), 0.75);
	fillLight.position.set(100, 0, 100);

	backLight = new THREE.DirectionalLight(0xffffff, 1.0);
	backLight.position.set(100, 0, -100).normalize();

	scene.add(keyLight);
	scene.add(fillLight);
	scene.add(backLight);
	
	/* Load model */
	var mtlLoader = new MTLLoader();
    mtlLoader.load(mtl_file, (materials) => {

        materials.preload();

        var objLoader = new OBJLoader();
        objLoader.setMaterials(materials);
        objLoader.load(obj_file, function (object) {
            scene.add(object);
        });
    });


    /* Renderer */
    
    renderer = new THREE.WebGLRenderer();
    renderer.setPixelRatio(window.devicePixelRatio);
    renderer.setSize(stage_width, stage_height);
    renderer.setClearColor(new THREE.Color("hsl(0, 0%, 10%)"));

    container.appendChild(renderer.domElement);

    /* Controls */

    controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.25;
          
    /* Events */
  
    window.addEventListener('keydown', function (e) {
    	e.stopPropagation();
    	if (e.code === 'KeyL') {
            lighting = !lighting;
            if (lighting) {
                ambient.intensity = 0.25;
                scene.add(keyLight);
                scene.add(fillLight);
                scene.add(backLight);
            } else {
                ambient.intensity = 1.0;
                scene.remove(keyLight);
                scene.remove(fillLight);
                scene.remove(backLight);
            }
        }	
    }, false);

    /* Start animation */
            
    animate();
};