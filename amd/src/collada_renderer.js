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
 * Encapsules the behavior for creating a Collada 3D model in Moodle.
 *
 * Manages the UI while operations are occuring, including rendering and manipulating the model.
 *
 * @module     mod_wavefront/collada_renderer
 * @class      collada_renderer
 * @package    mod_wavefront
 * @copyright  2022 Ian Wild
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.9
 */

import WebGL from 'mod_wavefront/WebGL';
import * as THREE from 'mod_wavefront/three';
import { ColladaLoader } from 'mod_wavefront/ColladaLoader';
import { OrbitControls } from 'mod_wavefront/OrbitControls';

import jQuery from 'jquery';

var cameras = [], controls_array = [], scenes = [], renderers = [];

var containers = [];
 
var stage_widths = [], stage_heights = [], scene_backcolours = []; 
var ambients = [], pointlights = [];
var clocks = [], mixers = [];

const animate = () => {
	renderers.forEach( function(renderer, i) {
		var delta = clocks[i].getDelta();

		if ( mixers[i] !== undefined ) {

			mixers[i].update( delta );

		}

	    renderer.render(scenes[i], cameras[i]);
	 	controls_array[i].update();
	});
	
	requestAnimationFrame( animate );
}

export const init = (stage) => {

	if (!WebGL.isWebGLAvailable() ) {			
	    const warning = WebGL.getWebGLErrorMessage();
		document.getElementById( 'container' ).appendChild( warning );
	    return true;
	}
	
	console.log(stage);
    
	var container = document.getElementById(stage);
	console.log(container);
	containers.push(container);
	
	// Get stage attributes
	var stage_width = jQuery(container).attr("data-stagewidth");
	console.log(stage_width);
	stage_widths.push(stage_width);
	var stage_height = jQuery(container).attr("data-stageheight");
	console.log(stage_height);
    stage_heights.push(stage_height);
    var backcol = jQuery(container).attr("data-backcol");
	console.log(backcol);
    scene_backcolours.push(backcol);
    
	// Get camera attributes
	var cameraangle = jQuery(container).attr("data-cameraangle");
    console.log(cameraangle);
	var camerafar = jQuery(container).attr("data-camerafar");
	console.log(camerafar);
	var camerax = jQuery(container).attr("data-camerax");
	console.log(camerax);
	var cameray = jQuery(container).attr("data-cameray");
	console.log(cameray);
	var cameraz = jQuery(container).attr("data-cameraz");
	console.log(cameraz);
	
	// Get model files
	var baseurl = jQuery(container).attr("data-baseurl");
	var base_url = decodeURIComponent(baseurl);
	console.log(base_url);
	
	var dae = jQuery(container).attr("data-dae");
	var dae_file = decodeURIComponent(dae);
	console.log(dae_file);
	
	// Create scene
	var scene = new THREE.Scene();
	scenes.push(scene);
	
	// Camera
	var SCREEN_WIDTH = stage_width, SCREEN_HEIGHT = stage_height;
	var VIEW_ANGLE = Number(cameraangle), ASPECT = SCREEN_WIDTH / SCREEN_HEIGHT, NEAR = 0.1, FAR = Number(camerafar);
	var camera = new THREE.PerspectiveCamera( VIEW_ANGLE, ASPECT, NEAR, FAR);
	cameras.push(camera);
	scene.add(camera);
	camera.position.set(Number(camerax),Number(cameray),Number(cameraz));	

	clock = new THREE.Clock();
	clocks.push(clock);
	
	/* Load model */
	var daeLoader = new ColladaLoader();
    daeLoader.load(dae_file, (collada) => {

		var animations = collada.animations;
		var avatar = collada.scene;

		avatar.traverse( function ( node ) {

			if ( node.isSkinnedMesh ) {

				node.frustumCulled = false;

			}

		} );

		mixer = new THREE.AnimationMixer( avatar );
		mixers.push(mixer);
		
		var action = mixer.clipAction( animations[ 0 ] ).play();

		scene.add( avatar );
		var iCol = Number("0x" + backcol); 
        scene.background = new THREE.Color(iCol);
		
		var ambientLight = new THREE.AmbientLight( 0xffffff, 0.2 );
		ambients.push[ambientLight];
		scene.add( ambientLight );

		var pointLight = new THREE.PointLight( 0xffffff, 0.8 );
		pointlights.push[pointLight];
		
		scene.add( camera );
		camera.add( pointLight );

		/* Renderer */

		var renderer = new THREE.WebGLRenderer( { antialias: true } );
		renderer.setPixelRatio(window.devicePixelRatio);
		renderer.setSize(stage_width, stage_height);
		renderer.setClearColor(new THREE.Color("hsl(0, 0%, 10%)"));
		renderers.push(renderer);
		container.appendChild(renderer.domElement);

		/* Controls */

		var controls = new OrbitControls( camera, renderer.domElement );
		controls.enableDamping = true;
		controls.dampingFactor = 0.25;
		controls_array.push(controls);
		controls.target.set(0, 0, 0);
		
		/* Remove the loading spinner */
		jQuery(container).next().remove();
			
	    /* Start animation */
		animate();
        
    }); 
};