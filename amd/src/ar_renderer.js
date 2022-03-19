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
 * @module     mod_wavefront/ar_renderer
 * @class      ar_renderer
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
import { ARButton } from 'mod_wavefront/ARButton';
import jQuery from 'jquery';

var container;
var camera, scene, renderer;
var controller;

var reticle;

var hitTestSource = null;
var hitTestSourceRequested = false;

var mtlLoader;
var monkeymesh;

export const init = (stage) => {

	var container = document.getElementById(stage);
    container = document.createElement( 'div' );
	document.body.appendChild( container );

	scene = new THREE.Scene();

	camera = new THREE.PerspectiveCamera( 70, window.innerWidth / window.innerHeight, 0.01, 20 );
	camera.position.z = 10;

	var light = new THREE.HemisphereLight( 0xffffff, 0xbbbbff, 1 );
	light.position.set( 0.5, 1, 0.25 );
	scene.add( light );

	//

	renderer = new THREE.WebGLRenderer( { antialias: true, alpha: true } );
	renderer.setPixelRatio( window.devicePixelRatio );
	renderer.setSize( window.innerWidth, window.innerHeight );
	renderer.xr.enabled = true;
	container.appendChild( renderer.domElement );


	////////////////////////////////////////////////////////////////////////////////////////////////
	mtlLoader = new MTLLoader();
	mtlLoader.setPath( "/var/www/moodle.ianwild.co.uk/public_html/mod/wavefront/samples/" );
	mtlLoader.load( 'greek_vase2.mtl', function ( materials ) {

		materials.preload();

		var objLoader = new OBJLoader();
		objLoader.setMaterials( materials );
		objLoader.setPath( "/var/www/moodle.ianwild.co.uk/public_html/mod/wavefront/samples/" );
		objLoader.load( 'greek_vase2.obj', function ( object ) {

			monkeymesh = object;
			scene.add( monkeymesh );

		} );

	} );

	var boxgeometry = new THREE.BoxBufferGeometry( 0.25, 0.25, 0.25 ).translate( 0, 0.1, 0 );

	function onSelect() {

		if ( reticle.visible ) {

			var material = new THREE.MeshPhongMaterial( { color: 0xffffff * Math.random() } );
			var mesh = new THREE.Mesh( boxgeometry, material );
			mesh.position.setFromMatrixPosition( reticle.matrix );
			//mesh.scale.y = Math.random() * 2 + 1;
			mesh.scale.set( 0.25, 0.25, 0.25 );
			scene.add( mesh );


		}

	}

	controller = renderer.xr.getController( 0 );
	controller.addEventListener( 'select', onSelect );
	scene.add( controller );

	reticle = new THREE.Mesh(
		new THREE.RingBufferGeometry( 0.15, 0.2, 32 ).rotateX( - Math.PI / 2 ),
		new THREE.MeshBasicMaterial()
	);
	reticle.matrixAutoUpdate = false;
	reticle.visible = false;
	scene.add( reticle );

	//

	window.addEventListener( 'resize', onWindowResize, false );
	
	animate();
};

function onWindowResize() {

	camera.aspect = window.innerWidth / window.innerHeight;
	camera.updateProjectionMatrix();

	renderer.setSize( window.innerWidth, window.innerHeight );

}

function animate() {

	renderer.setAnimationLoop( render );

}

function render( timestamp, frame ) {

	if ( frame ) {

		var referenceSpace = renderer.xr.getReferenceSpace();
		var session = renderer.xr.getSession();

		if ( hitTestSourceRequested === false ) {

			session.requestReferenceSpace( 'viewer' ).then( function ( referenceSpace ) {

				session.requestHitTestSource( { space: referenceSpace } ).then( function ( source ) {

					hitTestSource = source;

				} );

			} );

			session.addEventListener( 'end', function () {

				hitTestSourceRequested = false;
				hitTestSource = null;

			} );

			hitTestSourceRequested = true;

		}

		if ( hitTestSource ) {

			var hitTestResults = frame.getHitTestResults( hitTestSource );

			if ( hitTestResults.length ) {

				var hit = hitTestResults[ 0 ];

				reticle.visible = true;
				reticle.matrix.fromArray( hit.getPose( referenceSpace ).transform.matrix );

			} else {

				reticle.visible = false;

			}

		}

	}

	renderer.render( scene, camera );

}

		