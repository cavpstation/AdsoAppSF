<?php namespace Illuminate\Support\Facades; use Illuminate\Support\Str; use Illuminate\Http\JsonResponse; use Illuminate\Support\Traits\MacroableTrait; use Illuminate\Http\Response as IlluminateResponse; use Illuminate\Support\Contracts\ArrayableInterface; use Symfony\Component\HttpFoundation\StreamedResponse; use Symfony\Component\HttpFoundation\BinaryFileResponse; class Response { use MacroableTrait; public static function make($content = '', $status = 200, array $headers = array()) { return new IlluminateResponse($content, $status, $headers); } public static function view($view, $data = array(), $status = 200, array $headers = array()) { $app = Facade::getFacadeApplication(); return static::make($app['view']->make($view, $data), $status, $headers); } public static function json($data = array(), $status = 200, array $headers = array(), $options = 0) { if ($data instanceof ArrayableInterface) { $data = $data->toArray(); } return new JsonResponse($data, $status, $headers, $options); } public static function stream($callback, $status = 200, array $headers = array()) { return new StreamedResponse($callback, $status, $headers); } public static function download($file, $name = null, array $headers = array(), $disposition = 'attachment') { $response = new BinaryFileResponse($file, 200, $headers, true, $disposition); if ( ! is_null($name)) { return $response->setContentDisposition($disposition, $name, Str::ascii($name)); } return $response; } }
