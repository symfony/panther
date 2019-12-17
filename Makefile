DIR := ${CURDIR}
QA_IMAGE := jakzal/phpqa:alpine

static:
	docker run --rm -v $(DIR):/project -w /project $(QA_IMAGE) phpstan analyze
