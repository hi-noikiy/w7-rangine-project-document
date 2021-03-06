<?php

/**
 * WeEngine Document System
 *
 * (c) We7Team 2019 <https://www.w7.cc>
 *
 * This is not a free software
 * Using it under the license terms
 * visited https://www.w7.cc for more details
 */

namespace W7\App\Controller\Admin;

use W7\App\Controller\BaseController;
use W7\App\Exception\ErrorHttpException;
use W7\App\Model\Logic\ChapterLogic;
use W7\App\Model\Logic\SettingLogic;
use W7\App\Model\Service\CdnLogic;
use W7\Http\Message\Server\Request;

class UploadController extends BaseController
{
	public function image(Request $request)
	{
		$this->validate($request, [
			'file' => 'required|file|mimes:bmp,png,jpeg,jpg|max:2048',
			'chapter_id' => 'required',
			'document_id' => 'required',
		]);

		$user = $request->getAttribute('user');
		if (!$user->isManager && !$user->isFounder && !$user->isOperator) {
			throw new ErrorHttpException('您没有权限管理该文档');
		}

		$chapterId = intval($request->post('chapter_id'));
		$documentId = intval($request->post('document_id'));

		$chapter = ChapterLogic::instance()->getById($chapterId);
		if (empty($chapter) || $chapter->document_id != $documentId) {
			throw new ErrorHttpException('章节不存在');
		}

		$file = $request->file('file');

		$fileName = sprintf('/%s/%s/%s.%s', $chapter->document_id, $chapterId, irandom(32), pathinfo($file->getClientFilename(), PATHINFO_EXTENSION));
		try {
			$url = CdnLogic::instance()->channel(SettingLogic::KEY_COS)->uploadFile($fileName, $file->getTmpFile());
		} catch (\Throwable $e) {
			throw new ErrorHttpException($e->getMessage());
		}

		return $this->data(['url' => $url]);
	}
}
