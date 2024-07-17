@extends('templates.test')
{{--
<template>
    <div id="APP">
        <form @submit.prevent="submitHandler" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="file" name="file" @change="changeHandler($event)" />
            <button type="submit">SEND</button>
        </form>
    </div>
</template>
<script src="{{ asset('assets/js/vue.min.js') }}"></script>
<script src="{{ asset('assets/js/axios.min.js') }}"></script>
<script>
   const app = new Vue({
        el:"#APP",
        name: "App",
        data:()=>({
            file: null
        }),
        methods:{
            changeHandler(e){
                console.log(e);
                this.file = e.target.files[0]
            },
            submitHandler(){
                axios.post('http://test.loc/send', {file: this.file})
                    .then(res=>{
                        console.log(res);
                    })
            }
        }
    });
</script>

<style>
    #app {
        font-family: Avenir, Helvetica, Arial, sans-serif;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        text-align: center;
        color: #2c3e50;
        margin-top: 60px;
    }
</style>
 --}}

<form action="/ru/send" enctype="multipart/form-data" method="post">
    @csrf
    @method('POST')
    <input type="file" name="file" />
    <button type="submit">SEND</button>
</form>
